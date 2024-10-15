<?php

namespace App\Http\Controllers\api\admin\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class OrderController extends Controller
{
    public function __construct(private Order $orders){}

    public function orders(){
        $orders = $this->orders
        ->where('order_type', 'delivery')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $pending = $this->orders
        ->where('order_status', 'pending')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $confirmed = $this->orders
        ->where('order_status', 'confirmed')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $processing = $this->orders
        ->where('order_status', 'processing')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $out_for_delivery = $this->orders
        ->where('order_status', 'out_for_delivery')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $delivered = $this->orders
        ->where('order_status', 'delivered')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $returned = $this->orders
        ->where('order_status', 'returned')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $faild_to_deliver = $this->orders
        ->where('order_status', 'faild_to_deliver')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $canceled = $this->orders
        ->where('order_status', 'canceled')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $scheduled = $this->orders
        ->where('order_status', 'scheduled')
        ->with(['customer', 'user', 'branch'])
        ->get();

        return response()->json([
            'orders' => $orders,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'processing' => $processing,
            'out_for_delivery' => $out_for_delivery,
            'delivered' => $delivered,
            'returned' => $returned,
            'faild_to_deliver' => $faild_to_deliver,
            'canceled' => $canceled,
            'scheduled' => $scheduled,
        ]);
    }
}
