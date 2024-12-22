<?php

namespace App\Http\Controllers\api\customer\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\Setting;

class OrderController extends Controller
{
    public function __construct(private Order $orders, private Setting $settings){}

    public function upcomming(Request $request){
        // https://bcknd.food2go.online/customer/orders
        $orders = $this->orders
        ->where('user_id', $request->user()->id)
        ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'scheduled'])
        ->with('delivery')
        ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function order_history(Request $request){
        // https://bcknd.food2go.online/customer/orders/history
        $orders = $this->orders
        ->where('user_id', $request->user()->id)
        ->whereIn('order_status', ['delivered', 'faild_to_deliver', 'canceled'])
        ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function order_track($id){
        // https://bcknd.food2go.online/customer/orders/order_status/{id}
        $order = $this->orders
        ->where('id', $id)
        ->first();
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

        // Assuming $order->created_at is a valid date-time string in 'Y-m-d H:i:s' format
        $time = Carbon::createFromFormat('Y-m-d H:i:s', $order->created_at);
        
        // Get the time to add (e.g., '02:30:45')
        $time_to_add = $delivery_time->setting;  // Assuming this is something like '02:30:45'
        
        // Split the time string into hours, minutes, and seconds
        list($hours, $minutes, $seconds) = explode(':', $time_to_add);
        
        // Ensure that $hours, $minutes, and $seconds are integers
        $hours = (int)$hours;
        $minutes = (int)$minutes;
        $seconds = (int)$seconds;
        
        // Add the time to the original Carbon instance
        $time = $time->addHours($hours)->addMinutes($minutes)->addSeconds($seconds);
        
        // If you want to format the final time as 'H:i:s'
        $formattedTime = $time->format('H:i:s');
        $formattedTime = Carbon::createFromFormat('H:i:s', $formattedTime)->format('h:i:s A');
        
        
        return response()->json([
            'status' => $order->order_status,
            'delivery_id' => $order->delivery_id,
            'delivery_time' =>$delivery_time,
            'time_delivered' => $formattedTime
        ]);
    }

    // public function notification_sound(){
    //     // https://bcknd.food2go.online/customer/orders/notification_sound
    //     $notification_sound = $this->settings
    //     ->where('name', 'notification_sound')
    //     ->orderByDesc('id')
    //     ->first();
    //     if (empty($notification_sound)) {
    //         $notification_sound = null;
    //     }
    //     else{
    //         $notification_sound = url('storage/' . $notification_sound->setting);
    //     }

    //     return response()->json([
    //         'notification_sound' => $notification_sound
    //     ]);
    // }

    public function cancel($id){
        // https://bcknd.food2go.online/customer/orders/cancel/{id}
        $order = $this->orders
        ->where('id', $id)
        ->update([
            'order_status' => 'canceled'
        ]);

        return response()->json([
            'success' => 'You cancel order success'
        ]);
    }

    public function cancel_time(){
        // https://bcknd.food2go.online/customer/orders/cancel_time
        $cancel_time = $this->settings
        ->where('name', 'time_cancel')
        ->orderByDesc('id')
        ->first();
        $cancel_time = $cancel_time->setting ?? '00:00:00';

        return response()->json([
            'cancel_time' => $cancel_time
        ]);
    }
}
