<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Setting;
use App\Models\TimeSittings;
use App\Models\Branch;

class TimeSlotController extends Controller
{
    public function __construct(private Setting $settings,
    private TimeSittings $time_setting, private Branch $branches){}

    public function view(){
        // https://bcknd.food2go.online/admin/settings/business_setup/time_slot
        $time_slot = $this->settings
        ->where('name', 'time_setting')
        ->orderByDesc('id')
        ->first();
        $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
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
        $time_slot = $time_slot->setting;
        $time_slot = json_decode($time_slot);
        $time_slot = $time_slot->custom;
        $time_setting = $this->time_setting
        ->with('branch')
        ->get();
        $branches = $this->branches
        ->where('status', 1)
        ->get();

        return response()->json([
            'days' => $time_slot,
            'time_setting' => $time_setting,
            'branches' => $branches,
        ]);
        
    }

    public function add_custom(Request $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/time_slot/add_custom
        // "custom": ["Sunday","Monday"]
        $validator = Validator::make($request->all(), [
            'custom' => 'array',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
      
        $resturant_time = $request->resturant_time;
        $custom = $request->custom ?? [];
        $setting = [
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

    public function add_times(Request $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/time_slot/add_times
        // from, hours,  branch_id, minutes
        $validator = Validator::make($request->all(), [
            'from' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $value)) {
                        $fail('The '.$attribute.' must be a valid time in HH:MM:SS format.');
                    }
                },
            ],
            'hours' => 'required|numeric',
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
      
        $resturant_time = $request->resturant_time;
        // $time_setting = $this->time_setting
        // ->where('branch_id', $request->branch_id)
        // ->first();
        // if (!empty($time_setting)) {
        //     return response()->json([
        //         'errors' => 'branch is found'
        //     ],400);
        // }

        $time_setting = $this->time_setting->create([
            'from' => $request->from,
            'hours' => $request->hours,
            'minutes' => $request->minutes ?? 0,
            'branch_id' => $request->branch_id,
        ]);
        

        return response()->json([
            'resturant_time' => $time_setting,
            'request' => $request->all(),
        ]);
    }

    public function update_times(Request $request, $id){
        // https://bcknd.food2go.online/admin/settings/business_setup/time_slot/update_times/{id}
        // from, hours,  branch_id, 
        $validator = Validator::make($request->all(), [
            'from' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $value)) {
                        $fail('The '.$attribute.' must be a valid time in HH:MM:SS format.');
                    }
                },
            ],
            'hours' => 'required|numeric',
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
      
        // $time_setting = $this->time_setting
        // ->where('branch_id', $request->branch_id)
        // ->where('id', '!=', $id)
        // ->first();
        // if (!empty($time_setting)) {
        //     return response()->json([
        //         'errors' => 'branch is found'
        //     ],400);
        // }

        $resturant_time = $request->resturant_time;
        $time_setting = $this->time_setting
        ->where('id', $id)
        ->first();
        $time_setting->update([
            'from' => $request->from,
            'hours' => $request->hours,
            'minutes' => $request->minutes ?? 0,
            'branch_id' => $request->branch_id,
        ]);
        

        return response()->json([
            'resturant_time' => $time_setting,
            'request' => $request->all(),
        ]);
    }
    
}
