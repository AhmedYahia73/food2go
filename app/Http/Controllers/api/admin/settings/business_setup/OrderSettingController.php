<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Setting;

class OrderSettingController extends Controller
{
    public function __construct(private Setting $settings){}

    public function view(){
        // https://bcknd.food2go.online/admin/settings/business_setup/order_setting
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
        
        return response()->json([
            'order_setting' => $order_setting, 
        ]);
    }

    public function add(Request $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/order_setting/add
        // Key
        // min_price
        $validator = Validator::make($request->all(), [
            'min_price' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $daily = $request->daily;
        $custom = $request->custom;
        $order_setting = $this->settings
        ->where('name', 'order_setting')
        ->orderByDesc('id')
        ->first();
        $setting = [
            'min_order' => $request->min_price,
        ];
        $setting = json_encode($setting);
        if (empty($order_setting)) {
            $order_setting = $this->settings
            ->create([
                'name' => 'order_setting',
                'setting' => $setting,
            ]);
        } 
        else{
            $order_setting->update([
                'setting' => $setting,
            ]);
        }

        return response()->json([
            'order_setting' => $order_setting,
            'request' => $request->all(),
        ]);
    }
}
