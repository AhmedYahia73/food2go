<?php

namespace App\Http\Controllers\api\delivery\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Order;

class OrderController extends Controller
{
    public function __construct(private Order $orders){}

    public function orders(Request $request){
        // https://backend.food2go.pro/delivery/orders
        $orders = $this->orders
        ->where('delivery_id', $request->user()->id)
        ->whereIn('order_status', ['out_for_delivery', 'processing'])
        ->with(['address.zone' => function($query){
            $query->with(['city', 'branch']);
        }, 'details'])
        ->get();

        foreach ($orders as $order) {
            $product = [];
            $addon = [];
            $items = [];
            foreach ($order->details as $detail) {
                $product[$detail->product_index] = $detail->product;
                if (!empty($detail->addon)) {
                    $addon[$detail->product_index][] = $detail->addon;
                }
            }
            foreach ($product as $key => $item) {
                $item['addons'] = $addon[$key] ?? null;
                $item['count'] = $detail->count;
                $items[] = $item;
            }
            $order->items = $items;
        }

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function status(Request $request){
        // https://backend.food2go.pro/delivery/orders/status
        // Keys
        // order_id, order_status[out_for_delivery, delivered]
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'order_status' => 'required|in:out_for_delivery,delivered',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $orders = $this->orders
        ->where('id', $request->order_id)
        ->update([
            'order_status' => $request->order_status
        ]);

        return response()->json([
            'success' => 'You update success'
        ]);
    }
}
