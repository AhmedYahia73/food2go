<?php

namespace App\Http\Controllers\api\admin\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Order;
use App\Models\Delivery;
use App\Models\Branch;

class OrderController extends Controller
{
    public function __construct(private Order $orders, private Delivery $deliveries, 
    private Branch $branches){}

    public function orders(){
        // https://bcknd.food2go.online/admin/order
        $orders = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $pending = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'pending')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $confirmed = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'confirmed')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $processing = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'processing')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $out_for_delivery = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'out_for_delivery')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $delivered = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'delivered')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $returned = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'returned')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $faild_to_deliver = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'faild_to_deliver')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $canceled = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'canceled')
        ->with(['user', 'branch', 'delivery'])
        ->get();
        $scheduled = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
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

    public function notification(Request $request){
        // https://bcknd.food2go.online/admin/order/notification
        // Key
        // orders
        $total = 0;
        if ($request->orders) {
            $old_orders = $request->orders;
            $new_orders = $this->orders
            ->where('pos', 0)
            ->where('status', 1)
            ->count();
            $total = $new_orders - $old_orders;
        }

        return response()->json([
            'new_orders' => $total
        ]);
    }

    public function branches(){
        // https://bcknd.food2go.online/admin/order/branches
        $branches = $this->branches
        ->get();
        $branches->push([
            'id' => 0,
            'name' => 'All'
        ]);

        return response()->json([
            'branches' => $branches
        ]);
    }

    public function order_filter(Request $request){
        // https://bcknd.food2go.online/admin/order/filter
        // Key
        // from, to, branch_id, type
        $validator = Validator::make($request->all(), [
            'from' => 'date',
            'to' => 'date',
            'type' => 'required|in:all,pending,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        if ($request->type == 'all') {
            $orders = $this->orders
            ->where('pos', 0)
            ->where('status', 1)
            ->with(['user', 'branch', 'delivery'])
            ->orderBy('created_at')
            ->get();
        } else {
            $orders = $this->orders
            ->where('pos', 0)
            ->where('status', 1)
            ->where('order_status', $request->type)
            ->with(['user', 'branch', 'delivery'])
            ->orderBy('created_at')
            ->get();
        }
        
        if ($request->branch_id && $request->branch_id != 0) {
            $orders = $orders
            ->where('branch_id', $request->branch_id);
        }
        if ($request->from) {
            $orders = $orders
            ->where('order_date', '>=', $request->from);
        }
        if ($request->to) {
            $orders = $orders
            ->where('order_date', '<=', $request->to);
        }

        return response()->json([
            'orders' => array_values($orders->toArray())
        ]);
    }

    public function order($id){
        // https://bcknd.food2go.online/admin/order/order/{id}
        $order = $this->orders
        ->with(['user', 'branch', 'delivery', 'pament_method'])
        ->find($id); 
        $deliveries = $this->deliveries
        ->get();
        $order_status = ['pending', 'confirmed', 'processing', 'out_for_delivery',
        'delivered' ,'returned' ,'faild_to_deliver' ,'canceled' ,'scheduled'];

        return response()->json([
            'order' => $order,
            'deliveries' => $deliveries,
            'order_status' => $order_status
        ]);
    }

    public function invoice($id){
        // https://bcknd.food2go.online/admin/order/invoice/{id}
        $order = $this->orders
        ->with(['user', 'address.zone.city', 'branch', 'delivery'])
        ->find($id);

        return response()->json([
            'order' => $order
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
            ->update([
                'order_status' => $request->order_status, 
            ]);
        }

        return response()->json([
            'order_status' => $request->order_status
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
