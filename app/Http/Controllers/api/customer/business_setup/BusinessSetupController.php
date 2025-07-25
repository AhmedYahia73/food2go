<?php

namespace App\Http\Controllers\api\customer\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Maintenance;
use App\Models\CompanyInfo;
use App\Models\Setting;
use App\Models\TimeSittings;
use App\Models\Address;

class BusinessSetupController extends Controller
{
    public function __construct(private Maintenance $maintenance,
    private CompanyInfo $company_info, private Setting $settings,
    private TimeSittings $time_sitting, private Address $address){}

    public function arabicTime($carbonTime) {
        $time = $carbonTime->format('h:i');
        $period = $carbonTime->format('A') === 'AM' ? 'ص' : 'م';
        return $time . ' ' . $period;
    }

    public function business_setup(Request $request){
        // https://bcknd.food2go.online/api/business_setup
        // Maintenance status
        if (!empty($request->address_id) && empty($request->branch_id)) {
            $address = $this->address
            ->where('id', $request->address_id)
            ->first();
            $branch_id = $address?->zone?->branch_id ?? null;
            $request->merge([
                'branch_id' => $branch_id,
            ]);
        }
        $maintenance = $this->maintenance
        ->orderByDesc('id')
        ->first();
        $login_branch = true;
        $login_customer = true;
        $login_delivery = true;
        $login_web = true;
        if (($maintenance->start_date <= date('Y-m-d') && $maintenance->end_date >= date('Y-m-d') && $maintenance->status)
        || $maintenance->until_change && $maintenance->status) {
            if ($maintenance->all) {
                $login_branch = false;
                $login_customer = false;
                $login_delivery = false;
                $login_web = false;
            }
            if ($maintenance->branch) {
                $login_branch = false;
            }
            if ($maintenance->customer) {
                $login_customer = false;
            }
            if ($maintenance->delivery ) {
                $login_delivery = false;
            }
            if ($maintenance->web) {
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
        ->where('name', 'time_setting')
        ->orderByDesc('id')
        ->first();
        if (empty($time_slot)) {
            $setting = [
                'custom' => [],
            ];
            $setting = json_encode($setting);
            $time_slot = $this->settings
            ->create([
                'name' => 'time_setting',
                'setting' => $setting
            ]);
        }
        $time_sitting = $this->time_sitting
        ->where('branch_id', $request->branch_id ?? null)
        ->get();
        $today = Carbon::now()->format('l');
        $close_message = '';
        $open_flag = false;

        if($time_sitting->count() == 0){
            $open_flag = true;
        }
        else{
            $now = Carbon::now();
            foreach ($time_sitting as $item) { 
                $resturant_time = $item;
                $open_from = date('Y-m-d') . ' ' . $resturant_time->from;

                $open_from = Carbon::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d') . ' ' . $resturant_time->from);
                $open_to = $open_from->copy()->addHours(intval($resturant_time->hours))->addMinutes($resturant_time->minutes);
                if($now >= $open_from && $now <= $open_to){
                    $open_flag = true;
                    break;
                }
                else{
                    $open_flag = false;
                }
            }
            if (!$open_flag) {
                $open_from = Carbon::parse($time_sitting[0]->from);
                $open_to = Carbon::parse($time_sitting[$time_sitting->count() - 1]->from);
                $open_to = $open_to->addHours($time_sitting[$time_sitting->count() - 1]->hours);
                $open_from = $this->arabicTime($open_from);
                $open_to = $this->arabicTime($open_to);
                $close_message = 'مواعيد العمل من ' . $open_from . ' الى ' . $open_to;
            }
        }
        // if (empty($time_sitting)) {
        //     $open_flag = true;
        // }
        // else{
        //     $resturant_time = $time_sitting;
        //     $time_slot = json_decode($time_slot->setting);
        //     $days = $time_slot->custom;

        //     $open_from = date('Y-m-d') . ' ' . $resturant_time->from;

        //     if (!empty($open_from)) {
        //         $now = Carbon::now();

        //         $open_from = Carbon::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d') . ' ' . $resturant_time->from);
        //         $open_to = $open_from->copy()->addHours(intval($resturant_time->hours));
        //         $open_to = $open_from->copy()->addHours(intval($resturant_time->hours));
        //         $end = Carbon::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d') . ' ' . $open_to->format('H:i:s'));
        //         if($open_from->format('A') == 'PM' && $now->format('A') == 'AM'){
        //             $open_from = $open_from->subDay();
        //         }
        //         elseif($open_from > $end ){
        //             $open_from = $open_from->subDay();
        //         }
		// 		if($now >= $open_from && $now <= $open_to){
		// 			$open_flag = true;
		// 		}
		// 		else{
		// 			$open_flag = false;
		// 		}
        //     }
                // _________________________________________________________
                // if ($now->between($open_from, $open_to) ) {
                //     $open_flag = true;
                //     $close_message = '';
                // }
                // elseif(in_array($today, $days)){
                //     $close_message = 'اليوم اجازة';
                // }
                // elseif(!$now->between($open_from, $open_to)){
                //     $close_message = 'مواعيد العمل من ' . $open_from->format('h:i A') . ' الى ' . $open_to->format('h:i A');
                // }

        return response()->json([ 
            'login_web' => $login_web,
            'company_info' => $company_info,
            'currency' => $company_info->currency ?? null,
            'min_order' => floatval($min_order),
            'time_slot' =>  $time_slot,
            'today' => $today, 
            'open_flag' => $open_flag,
            'login_branch' => $login_branch,
            'login_customer' => $login_customer,
            'login_delivery' => $login_delivery,
            'login_web' => $login_web,
            'close_message' => $close_message,
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
        ['login' => 'otp', 'verification' => 'email'];

        return response()->json([
            'customer_login' => $customer_login,
        ]);
    }
}
