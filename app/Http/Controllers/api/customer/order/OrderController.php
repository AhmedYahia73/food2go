<?php

namespace App\Http\Controllers\api\customer\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

        return response()->json([
            'status' => $order->order_status,
            'delivery_id' => $order->delivery_id,
        ]);
    }

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
