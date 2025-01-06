<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Setting;

class TimeSlotController extends Controller
{
    public function __construct(private Setting $settings){}

    public function view(){
        // https://bcknd.food2go.online/admin/settings/business_setup/time_slot
        $time_slot = $this->settings
        ->where('name', 'time_slot')
        ->orderByDesc('id')
        ->first();
        $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
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
        
        return response()->json([
            'time_slot' => $time_slot,
            'days' => $days
        ]);
        
    }

    public function add(Request $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/time_slot/add
        // "daily": [{"'from'": "00:10:00","'to'": "00:11:00"}],
        // "custom": ["Sunday","Monday"]
        $validator = Validator::make($request->all(), [
            'daily' => 'required|array',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $daily = $request->daily;
        $custom = $request->custom ?? [];
        $setting = [
            'daily' => $daily,
            'custom' => $custom,
        ];
        $setting = json_encode($setting);
        $time_slot = $this->settings
        ->where('name', 'time_slot')
        ->orderByDesc('id')
        ->first();
        if (empty($time_slot)) {
            $time_slot = $this->settings
            ->create([
                'name' => 'time_slot',
                'setting' => $setting
            ]);
        } 
        else{
            $time_slot->update([
                'setting' => $setting
            ]);
        }

        return response()->json([
            'time_slot' => $time_slot,
            'request' => $request->all(),
        ]);
    }
}
