<?php

namespace App\Http\Controllers\api\admin\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Order;
use App\Models\Delivery;

class OrderController extends Controller
{
    public function __construct(private Order $orders, private Delivery $deliveries){}

    public function orders(){
        // https://bcknd.food2go.online/admin/order
        $orders = $this->orders
        ->where('pos', 0)
        ->where('status', 1)
        ->where('order_type', 'delivery')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $pending = $this->orders
        ->where('pos', 0)
        ->where('status', 1)
        ->where('order_status', 'pending')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $confirmed = $this->orders
        ->where('pos', 0)
        ->where('status', 1)
        ->where('order_status', 'confirmed')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $processing = $this->orders
        ->where('pos', 0)
        ->where('status', 1)
        ->where('order_status', 'processing')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $out_for_delivery = $this->orders
        ->where('pos', 0)
        ->where('status', 1)
        ->where('order_status', 'out_for_delivery')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $delivered = $this->orders
        ->where('pos', 0)
        ->where('status', 1)
        ->where('order_status', 'delivered')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $returned = $this->orders
        ->where('pos', 0)
        ->where('status', 1)
        ->where('order_status', 'returned')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $faild_to_deliver = $this->orders
        ->where('pos', 0)
        ->where('status', 1)
        ->where('order_status', 'faild_to_deliver')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $canceled = $this->orders
        ->where('pos', 0)
        ->where('status', 1)
        ->where('order_status', 'canceled')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $scheduled = $this->orders
        ->where('pos', 0)
        ->where('status', 1)
        ->where('order_status', 'scheduled')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $deliveries = $this->deliveries
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
            'deliveries' => $deliveries
        ]);
    }

    public function status($id, Request $request){
        // https://bcknd.food2go.online/admin/order/status/{id}
        // Keys
        // order_status, order_number
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:delivery,pending,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        if ($request->order_status == 'confirmed') { 
            $orders = $this->orders
            ->where('id', $id)
            ->update([
                'order_status' => $request->order_status,
                'order_number' => $request->order_number,
            ]);
        } else {
        
            $orders = $this->orders
            ->where('id', $id)
            ->first();
            $orders->update([
                'order_status' => $request->order_status, 
            ]);
        }

        return response()->json([
            'order_status' => $request->order_status,
            'delivery_id' => $orders->delivery_id,
        ]);
    }

    public function delivery(Request $request){
        // https://bcknd.food2go.online/admin/order/delivery
        // Keys
        // delivery_id, order_id, order_number
        $validator = Validator::make($request->all(), [
            'delivery_id' => 'required|exists:deliveries,id',
            'order_id' => 'required|exists:orders,id',
            'order_number' => 'required|numeric'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $order = $this->orders
        ->where('id', $request->order_id)
        ->update([
            'delivery_id' => $request->delivery_id,
            'order_number' => $request->order_number
        ]);

        return response()->json([
            'success' => 'You select delivery success'
        ]);
    }
}
