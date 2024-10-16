<?php

namespace App\Http\Controllers\api\admin\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Order;

class OrderController extends Controller
{
    public function __construct(private Order $orders){}

    public function orders(){
        $orders = $this->orders
        ->where('pos', 0)
        ->where('order_type', 'delivery')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $pending = $this->orders
        ->where('pos', 0)
        ->where('order_status', 'pending')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $confirmed = $this->orders
        ->where('pos', 0)
        ->where('order_status', 'confirmed')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $processing = $this->orders
        ->where('pos', 0)
        ->where('order_status', 'processing')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $out_for_delivery = $this->orders
        ->where('pos', 0)
        ->where('order_status', 'out_for_delivery')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $delivered = $this->orders
        ->where('pos', 0)
        ->where('order_status', 'delivered')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $returned = $this->orders
        ->where('pos', 0)
        ->where('order_status', 'returned')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $faild_to_deliver = $this->orders
        ->where('pos', 0)
        ->where('order_status', 'faild_to_deliver')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $canceled = $this->orders
        ->where('pos', 0)
        ->where('order_status', 'canceled')
        ->with(['customer', 'user', 'branch'])
        ->get();
        $scheduled = $this->orders
        ->where('pos', 0)
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

    public function status($id, Request $request){
        // Keys
        // order_status
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:delivery,pending,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $orders = $this->orders
        ->where('id', $id)
        ->update([
            'order_status' => $request->order_status
        ]);

        return response()->json([
            'order_status' => $request->order_status
        ]);
    }
}
