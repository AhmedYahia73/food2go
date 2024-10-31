<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Setting;

class SettingController extends Controller
{
    public function __construct(private Setting $settings){}

    public function view_time_cancel_order(){
        $time = $this->settings
        ->where('name', 'time_cancel')
        ->orderByDesc('id')
        ->first();

        return response()->json([
            'time' => $time
        ]);
    }

    public function update_time_cancel_order(Request $request){
        // Key
        // time
        $setting = $this->settings
        ->where('name', 'time_cancel')
        ->orderByDesc('id')
        ->first();
        if (empty($setting)) {
            $this->setting
            ->create([
                'name' => 'time_cancel',
                'setting' => $request->time,
            ]);
        } 
        else {
            $setting->update([
                'setting' => $request->time
            ]);
        }
        
        return response()->json([
            'success' => 'You add time of cancel order success'
        ]);
    }
}
