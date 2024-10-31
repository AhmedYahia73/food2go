<?php

namespace App\Http\Controllers\api\customer\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class OrderController extends Controller
{
    public function __construct(private Order $orders){}

    public function upcomming(Request $request){
        // https://backend.food2go.pro/customer/orders
        $orders = $this->orders
        ->where('user_id', $request->user()->id)
        ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'scheduled'])
        ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function order_history(Request $request){
        // https://backend.food2go.pro/customer/orders/history
        $orders = $this->orders
        ->where('user_id', $request->user()->id)
        ->whereIn('order_status', ['delivered', 'faild_to_deliver', 'canceled'])
        ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function order_track($id){
        // https://backend.food2go.pro/customer/orders/order_status/{id}
        $order = $this->orders
        ->where('id', $id)
        ->first()->order_status;

        return response()->json([
            'status' => $order
        ]);
    }

    public function cancel($id){
        // https://backend.food2go.pro/customer/orders/cancel/{id}
        $order = $this->orders
        ->where('id', $id)
        ->update([
            'order_status' => 'canceled'
        ]);

        return response()->json([
            'success' => 'You cancel order success'
        ]);
    }
}
