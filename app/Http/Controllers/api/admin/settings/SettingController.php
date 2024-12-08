<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Setting;

class SettingController extends Controller
{
    public function __construct(private Setting $settings){}
    use image;

    public function view_time_cancel_order(){
        // https://bcknd.food2go.online/admin/settings/view_time_cancel
        $time = $this->settings
        ->where('name', 'time_cancel')
        ->orderByDesc('id')
        ->first();

        return response()->json([
            'time' => $time
        ]);
    }

    public function update_time_cancel_order(Request $request){
        // https://bcknd.food2go.online/admin/settings/update_time_cancel
        // Key
        // time
        $validator = Validator::make($request->all(), [
            'time' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $setting = $this->settings
        ->where('name', 'time_cancel')
        ->orderByDesc('id')
        ->first();
        if (empty($setting)) {
            $this->settings
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

    public function resturant_time(){
        // https://bcknd.food2go.online/admin/settings/resturant_time
        $time = $this->settings
        ->where('name', 'resturant_time')
        ->orderByDesc('id')
        ->first();
        if (!empty($time)) {
            $time = $time->setting;
            $time = json_decode($time) ?? $time;
        }

        return response()->json([
            'restuarant_time' => $time
        ]);
    }

    public function resturant_time_update(Request $request){
        // https://bcknd.food2go.online/admin/settings/resturant_time_update
        // Keys
        // from, to
        $validator = Validator::make($request->all(), [
            'from' => 'required',
            'to' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $time = $this->settings
        ->where('name', 'resturant_time')
        ->orderByDesc('id')
        ->first();
        if (!empty($time)) {
            $time->update([
                'setting' => json_encode([
                    'from' => $request->from,
                    'to' => $request->to,
                ]),
            ]);
        }
        else{
            $this->settings->create([
                'name' => 'resturant_time',
                'setting' => json_encode([
                    'from' => $request->from,
                    'to' => $request->to,
                ]),
            ]);
        }

        return response()->json([
            'success' => 'You change times success'
        ]);
    }

    public function tax(){
        // https://bcknd.food2go.online/admin/settings/tax_type
        $tax = $this->settings
        ->where('name', 'tax')
        ->orderByDesc('id')
        ->first();
        if (!empty($tax)) {
            $tax = $tax->setting;
        }
        else {
            $tax = $this->settings
            ->create([
                'name' => 'tax',
                'setting' => 'included',
            ]);
            $tax = $tax->setting;
        }

        return response()->json([
            'tax' => $tax
        ]);
    }

    public function tax_update(Request $request){
        // https://bcknd.food2go.online/admin/settings/tax_update
        // Keys
        // tax[included, excluded]
        $validator = Validator::make($request->all(), [
            'tax' => 'required|in:included,excluded', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $tax = $this->settings
        ->where('name', 'tax')
        ->orderByDesc('id')
        ->first();
        if (!empty($tax)) {
            $tax->update([
                'setting' => $request->tax
            ]);
        }
        else {
            $tax = $this->settings
            ->create([
                'name' => 'tax',
                'setting' => $request->tax,
            ]);
        }

        return response()->json([
            'success' => 'You change data success'
        ]);
    }

    public function delivery_time(){
        // https://bcknd.food2go.online/admin/settings/delivery_time
        $delivery_time = $this->settings
        ->where('name', 'delivery_time')
        ->orderByDesc('id')
        ->first();
        if (empty($delivery_time)) {
            $delivery_time = $this->settings
            ->create([
                'name' => 'delivery_time',
                'setting' => '00:30:00',
            ]);
        }

        return response()->json([
            'delivery_time' => $delivery_time
        ]);
    }

    public function delivery_time_update(Request $request){
        // https://bcknd.food2go.online/admin/settings/delivery_time_update
        // Keys
        // delivery_time
        $validator = Validator::make($request->all(), [
            'delivery_time' => 'required', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $delivery_time = $this->settings
        ->where('name', 'delivery_time')
        ->orderByDesc('id')
        ->first();
        if (empty($delivery_time)) {
            $delivery_time = $this->settings
            ->create([
                'name' => 'delivery_time',
                'setting' => '00:30:00',
            ]);
        }
        else{
            $delivery_time->update([
                'setting' => $request->delivery_time
            ]);
        }

        return response()->json([
            'delivery_time' => $delivery_time
        ]);
    }

    public function preparing_time(){
        // https://bcknd.food2go.online/admin/settings/preparing_time
        $preparing_time = $this->settings
        ->where('name', 'preparing_time')
        ->orderByDesc('id')
        ->first();
        if (empty($preparing_time)) {
            $preparing_arr = [
                'days' => 0,
                'hours' => 0,
                'minutes' => 30,
                'seconds' => 0
            ];
            $preparing_time = $this->settings
            ->create([
                'name' => 'preparing_time',
                'setting' => json_encode($preparing_arr),
            ]);
        }

        return response()->json([
            'preparing_time' => $preparing_time
        ]);
    }

    public function preparing_time_update(Request $request){
        // https://bcknd.food2go.online/admin/settings/preparing_time_update
        // Keys
        // days, hours, minutes, seconds
        $validator = Validator::make($request->all(), [
            'days' => 'required', 
            'hours' => 'required', 
            'minutes' => 'required', 
            'seconds' => 'required', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $preparing_time = $this->settings
        ->where('name', 'preparing_time')
        ->orderByDesc('id')
        ->first();
        if (empty($preparing_time)) {
            $preparing_arr = [
                'days' => 0,
                'hours' => 0,
                'minutes' => 30,
                'seconds' => 0
            ];
            $preparing_time = $this->settings
            ->create([
                'name' => 'preparing_time',
                'setting' => json_encode($preparing_arr),
            ]);
        }
        else{
            $preparing_arr = [
                'days' => $request->days,
                'hours' => $request->hours,
                'minutes' => $request->minutes,
                'seconds' => $request->seconds,
            ];
            $preparing_time->update([
                'setting' => json_encode($preparing_arr)
            ]);
        }

        return response()->json([
            'preparing_time' => $preparing_time
        ]);
    }

    public function notification_sound(){
        // https://bcknd.food2go.online/admin/settings/notification_sound
        $notification_sound = $this->settings
        ->where('name', 'notification_sound')
        ->orderByDesc('id')
        ->first();
        if (empty($notification_sound)) {
            $notification_sound = null;
        }
        else{
            $notification_sound = url('storage/' . $notification_sound->setting);
        }

        return response()->json([
            'notification_sound' => $notification_sound
        ]);
    }

    public function notification_sound_update(Request $request){
        // https://bcknd.food2go.online/admin/settings/notification_sound_update
        // Keys
        // notification_sound
        $validator = Validator::make($request->all(), [
            'notification_sound' => 'required', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $notification_sound = $this->settings
        ->where('name', 'notification_sound')
        ->orderByDesc('id')
        ->first();
        if (empty($notification_sound)) {
            $sound = $this->upload($request, 'notification_sound', 'admin/settings/notificatins/sound');
            $notification_sound = $this->settings
            ->create([
                'name' => 'notification_sound',
                'setting' => $sound
            ]);
        }
        else{
            $sound = $this->upload($request, 'notification_sound', 'admin/settings/notificatins/sound');
            $this->deleteImage($notification_sound->setting);
            $notification_sound
            ->update([
                'setting' => $sound
            ]);
        }
        $notification_sound = url('storage/' . $notification_sound->setting);

        return response()->json([
            'notification_sound' => $notification_sound
        ]);
    }
}
