<?php

namespace App\Http\Controllers\api\customer\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Maintenance;
use App\Models\CompanyInfo;
use App\Models\Setting;

class BusinessSetupController extends Controller
{
    public function __construct(private Maintenance $maintenance,
    private CompanyInfo $company_info, private Setting $settings){}

    public function business_setup(Request $request){
        // https://bcknd.food2go.online/api/business_setup
        // Maintenance status
        $role = $request->user()->role;
        $maintenance = $this->maintenance
        ->orderByDesc('id')
        ->first();
        $login = true;
        $login_web = true;
        if (($maintenance->start_date <= date('Y-m-d') && $maintenance->end_date >= date('Y-m-d') && $maintenance->status)
        || $maintenance->until_change && $maintenance->status) {
            if ($maintenance->all) {
                $login = false;
            }
            if ($maintenance->branch && $role == 'branch') {
                $login = false;
            }
            if ($maintenance->customer && $role == 'customer') {
                $login = false;
            }
            if ($maintenance->delivery && $role == 'delivery') {
                $login = false;
            }
            if ($maintenance->web || !$login) {
                $login_web = false;
            }
        }
        // Company Info
        $company_info = $this->company_info
        ->orderByDesc('id')
        ->first();
        // Order Settings      
        $order_setting = $this->settings
        ->where('name', 'order_setting')
        ->orderByDesc('id')
        ->first(); 
        if (empty($order_setting)) {  
            $setting = [
                'min_order' => 0,
            ];
            $setting = json_encode($setting);
            $order_setting = $this->settings
            ->create([
                'name' => 'order_setting',
                'setting' => $setting,
            ]);
        }
        $order_setting = json_decode($order_setting->setting) ?? null;
        $min_order = $order_setting->min_order ?? 0;
        // Time slot
        $time_slot = $this->settings
        ->where('name', 'time_slot')
        ->orderByDesc('id')
        ->first();
        if (empty($time_slot)) {
            $setting = [
                'daily' => [],
                'custom' => [],
            ];
            $setting = json_encode($setting);
            $time_slot = $this->settings
            ->create([
                'name' => 'time_slot',
                'setting' => $setting
            ]);
        }
        $time_slot = json_decode($time_slot->setting) ?? [
            'daily' => [],
            'custom' => [],
        ];
        $today = Carbon::now()->format('l');

        return response()->json([
            'login' => $login,
            'login_web' => $login_web,
            'company_info' => $company_info,
            'currency' => $company_info->currency ?? null,
            'min_order' => $min_order,
            'time_slot' =>  $time_slot,
            'today' => $today
        ]);
    }

    public function customer_login(){
        // https://bcknd.food2go.online/api/customer_login
        $customer_login = $this->settings
        ->where('name', 'customer_login')
        ->orderByDesc('id')
        ->first();
        if (empty($customer_login)) {
            $setting = ['login' => 'manuel', 'verification' => null,];
            $setting = json_encode($setting);
            $customer_login = $this->settings
            ->create([
                'name' => 'customer_login',
                'setting' => $setting
            ]);
        }
        $customer_login = json_decode($customer_login->setting) ?? 
        ['login' => 'manuel', 'verification' => null];

        
        return response()->json([
            'customer_login' => $customer_login,
        ]);
    }
}
