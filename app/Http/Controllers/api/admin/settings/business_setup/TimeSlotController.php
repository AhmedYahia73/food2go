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
        ->where('name', 'time_setting')
        ->orderByDesc('id')
        ->first();
        $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        if (empty($time_slot)) {
            $setting = [
                'resturant_time' => [
                    'from' => '00:00:00',
                    'hours' => '22',
                ],
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
            'resturant_time' => $time_slot,
            'days' => $days
        ]);
        
    }

    public function add(Request $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/time_slot/add
        // "resturant_time": {"'from'": "00:10:00","'hours'": 22},
        // "custom": ["Sunday","Monday"]
        $validator = Validator::make($request->all(), [
            'resturant_time' => 'required|array',
            'resturant_time.from' => 'required|regex:/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/',
            'resturant_time.hours' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
      
        $resturant_time = $request->resturant_time;
        $custom = $request->custom ?? [];
        $setting = [
            'resturant_time' => $resturant_time,
            'custom' => $custom,
        ];
        $setting = json_encode($setting);
        $time_slot = $this->settings
        ->where('name', 'time_setting')
        ->orderByDesc('id')
        ->first();
        if (empty($time_slot)) {
            $time_slot = $this->settings
            ->create([
                'name' => 'time_setting',
                'setting' => $setting
            ]);
        } 
        else{
            $time_slot->update([
                'setting' => $setting
            ]);
        }

        return response()->json([
            'resturant_time' => $time_slot,
            'request' => $request->all(),
        ]);
    }
}
