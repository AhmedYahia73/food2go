<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Setting;
use App\Models\Order;

class SettingController extends Controller
{
    public function __construct(private Setting $settings,
    private Order $order){}
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
                'errors' => $validator->errors(),
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
                'errors' => $validator->errors(),
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
                'errors' => $validator->errors(),
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
                'errors' => $validator->errors(),
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
                'errors' => $validator->errors(),
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
                'errors' => $validator->errors(),
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

    public function cancelation_notification(Request $request){
        // https://bcknd.food2go.online/admin/settings/cancelation_notification
        $re_notification = $this->settings
        ->where('name', 'repeated')
        ->orderByDesc('id')
        ->first(); 
        if (empty($re_notification)) { 
            $re_notification = $this->settings
            ->create([
                'name' => 'repeated',
                'setting' => 1
            ]);
        }
        $r_online_noti = $this->settings
        ->where('name', 'r_online_noti')
        ->orderByDesc('id')
        ->first(); 
        if (empty($r_online_noti)) { 
            $r_online_noti = $this->settings
            ->create([
                'name' => 'r_online_noti',
                'setting' => 0
            ]);
        }

        return response()->json([
            'repeated_notification' => $re_notification->setting,
            'r_online_noti' => $r_online_noti->setting,
        ]);
    }

    public function update_cancelation_notification(Request $request){
        // https://bcknd.food2go.online/admin/settings/update_cancelation_notification
        // Keys
        // repeated
        $validator = Validator::make($request->all(), [
            'repeated' => 'required|boolean',
            'r_online_noti' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $notification_sound = $this->settings
        ->where('name', 'repeated')
        ->orderByDesc('id')
        ->first();
        if (empty($notification_sound)) { 
            $notification_sound = $this->settings
            ->create([
                'name' => 'repeated',
                'setting' => $request->repeated
            ]);
        }
        else{ 
            $notification_sound
            ->update([
                'setting' => $request->repeated
            ]);
        } 
        $notification_sound = $this->settings
        ->where('name', 'r_online_noti')
        ->orderByDesc('id')
        ->first();
        if (empty($notification_sound)) { 
            $notification_sound = $this->settings
            ->create([
                'name' => 'r_online_noti',
                'setting' => $request->r_online_noti
            ]);
        }
        else{ 
            $notification_sound
            ->update([
                'setting' => $request->r_online_noti
            ]);
        } 

        return response()->json([
            'success' => $request->repeated ? 'active' : 'banned',
            "r_online_noti" => $request->r_online_noti ? 'active' : 'banned',
        ]);
    }

    public function cancelation(Request $request){
        $order = $this->order
        ->where('canceled_noti', 0)
        ->where('order_status', 'canceled')
        ->get();
        $settings = $this->settings
        ->where('name', 'repeated')
        ->orderByDesc('id')
        ->first();
        if (empty($settings)) {
            $settings = $this->settings
            ->create([
                'name' => 'repeated',
                'setting' => 0
            ]);
        }
        $repeated = $settings->setting;
        if ($repeated == '0') {
            $this->order
            ->where('canceled_noti', 0)
            ->where('order_status', 'canceled')
            ->update([
                'canceled_noti' => 1
            ]);
        }

        return response()->json([
            'orders' => $order,
        ]);
    }

    public function cancelation_status(Request $request, $id){
        $order = $this->order
        ->where('id', $id) 
        ->update([
            'canceled_noti' => 1
        ]);

        return response()->json([
            'success' => 'You change status success',
        ]);
    }
}
