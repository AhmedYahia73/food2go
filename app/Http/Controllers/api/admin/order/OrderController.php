<?php

namespace App\Http\Controllers\api\admin\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Order;
use App\Models\Delivery;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\LogOrder;
use App\Models\User;

class OrderController extends Controller
{
    public function __construct(private Order $orders, private Delivery $deliveries, 
    private Branch $branches, private Setting $settings, private User $user,
    private LogOrder $log_order){}

    public function orders(){
        // https://bcknd.food2go.online/admin/order
        $orders = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $pending = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'pending')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $confirmed = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'confirmed')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $processing = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'processing')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $out_for_delivery = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'out_for_delivery')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $delivered = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'delivered')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $returned = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'returned')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $faild_to_deliver = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'faild_to_deliver')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $canceled = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'canceled')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $scheduled = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'scheduled')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();

        $all_data = [
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
        ];

        // _____________________________________________________________
        
        $orders = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $pending = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'pending')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $confirmed = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'confirmed')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $processing = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'processing')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $out_for_delivery = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'out_for_delivery')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $delivered = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'delivered')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $returned = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'returned')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $faild_to_deliver = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'faild_to_deliver')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $canceled = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'canceled')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $scheduled = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'scheduled')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->get();
        $refund = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereDate('created_at', date('Y-m-d'))
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'refund')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
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
            'refund' => $refund,
            'canceled' => $canceled,
            'scheduled' => $scheduled,
            'all_data' => $all_data,
            'deliveries' => $deliveries,
        ]);
    }

    public function count_orders(){
        // https://bcknd.food2go.online/admin/order/count
        $orders = $this->orders 
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();
        $pending = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'pending')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();
        $confirmed = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'confirmed')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();
        $processing = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'processing')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();
        $out_for_delivery = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'out_for_delivery')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();
        $delivered = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'delivered')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();
        $returned = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'returned')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();
        $faild_to_deliver = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'faild_to_deliver')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();
        $canceled = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'canceled')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();
        $scheduled = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'scheduled')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();
        $refund = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'refund')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
        ->count();

        return response()->json([
            'orders' => $orders,
            'pending' => $pending,
            'confirmed' => $confirmed,
            'processing' => $processing,
            'out_for_delivery' => $out_for_delivery,
            'delivered' => $delivered,
            'returned' => $returned,
            'refund' => $refund,
            'faild_to_deliver' => $faild_to_deliver,
            'canceled' => $canceled,
            'scheduled' => $scheduled,
        ]);
    }

    public function orders_data(Request $request){
        // https://bcknd.food2go.online/admin/order/data
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:all,pending,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled,refund',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        if ($request->order_status == 'all') {
            $orders = $this->orders
            ->select('id', 'date', 'user_id', 'branch_id', 'amount',
            'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
            'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
            'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
            'status', 'points', 'rejected_reason', 'transaction_id')
            ->where('pos', 0)
        ->whereNull('captain_id')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->orderByDesc('id')
            ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
            ->get();
        } 
        else {
            $orders = $this->orders
            ->select('id', 'date', 'user_id', 'branch_id', 'amount',
            'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
            'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
            'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
            'status', 'points', 'rejected_reason', 'transaction_id')
            ->where('pos', 0)
        ->whereNull('captain_id')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where('order_status', $request->order_status)
            ->orderByDesc('id')
            ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
            ->get();
        }

        return response()->json([
            'orders' => $orders
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
        ->whereNull('captain_id')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->count();
            $total = $new_orders - $old_orders;
        }
        $new_orders = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc('id')
        ->limit($total)->pluck('id');

        return response()->json([
            'new_orders' => $total,
            'order_id' => $new_orders->last() ?? null,
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
            'type' => 'in:all,pending,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled,refund'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        if ($request->type) {
            if ($request->type == 'all') {
                $orders = $this->orders
                ->select('id', 'date', 'user_id', 'branch_id', 'amount',
                'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
                'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
                'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
                'status', 'points', 'rejected_reason', 'transaction_id')
                ->where('pos', 0)
        ->whereNull('captain_id')
                ->where('status', '!=', 2)
                ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
                ->orderBy('created_at')
                ->get();
            } else {
                $orders = $this->orders
                ->select('id', 'date', 'user_id', 'branch_id', 'amount',
                'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
                'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
                'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
                'status', 'points', 'rejected_reason', 'transaction_id')
                ->where('pos', 0)
        ->whereNull('captain_id')
                ->where('status', '!=', 2)
                ->where('order_status', $request->type)
                ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
                ->orderBy('created_at')
                ->get();
            }
        }
        else{
            $orders = $this->orders
            ->select('id', 'date', 'user_id', 'branch_id', 'amount',
            'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
            'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
            'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
            'status', 'points', 'rejected_reason', 'transaction_id')
            ->where('pos', 0)
        ->whereNull('captain_id')
            ->with(['user', 'branch', 'address.zone', 'payment_method', 'delivery'])
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
        ->select('id', 'receipt', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 'order_details',
        'status', 'points', 'rejected_reason', 'transaction_id', 'customer_cancel_reason', 
        'admin_cancel_reason')
        ->with(['user.orders' => function($query){
            $query->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id',
        'status', 'points', 'rejected_reason', 'transaction_id');
        }, 'branch', 'delivery', 'payment_method', 'address'])
        ->find($id);
        $order->user->count_orders = count($order->user->orders);
        if (!empty($order->branch)) {
            $order->branch->count_orders = count($order->branch->orders);
        }
        if (!empty($order->delivery_id)) {
            $order->delivery->count_orders = count($order->delivery->orders_items);
        }
        $deliveries = $this->deliveries
        ->get();
        $order_status = ['pending', 'processing', 'out_for_delivery',
        'delivered' ,'canceled', 'confirmed', 'scheduled', 'returned' ,'faild_to_deliver'];
        $preparing_time = $order->branch->food_preparion_time ?? '00:30';
        // if (empty($preparing_time)) {
        $time_parts = explode(':', $preparing_time);

        // Get hours, minutes, and seconds
        $hours = $time_parts[0];
        $minutes = $time_parts[1]; 
            $preparing_arr = [
                'days' => 0,
                'hours' => $hours,
                'minutes' => $minutes,
                'seconds' => 0,
            ];
        //     $preparing_time = $this->settings
        //     ->create([
        //         'name' => 'preparing_time',
        //         'setting' => json_encode($preparing_arr),
        //     ]);
        // }
        // $preparing_time = json_decode($preparing_time->setting);

        return response()->json([
            'order' => $order,
            'deliveries' => $deliveries,
            'order_status' => $order_status,
            'preparing_time' => $preparing_arr
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
        // if canceled => key admin_cancel_reason
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:delivery,pending,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled,refund',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $order = $this->orders
        ->where('id', $id)
        ->first();
        if (empty($order)) {
            return response()->json([
                'error' => 'order is not found'
            ], 400);
        }
        $this->log_order
        ->create([
            'order_id' => $id,
            'admin_id' => $request->user()->id,
            'from_status' => $order->order_status,
            'to_status' => $request->order_status,
        ]);
        if ($request->order_status == 'processing') { 
            $order->update([
                'order_status' => $request->order_status,
                'order_number' => $request->order_number ?? null,
            ]);
        }
        elseif($request->order_status == 'canceled'){
            // Key
            // admin_cancel_reason
            $validator = Validator::make($request->all(), [
                'admin_cancel_reason' => 'required',
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'error' => $validator->errors(),
                ],400);
            }
            $order->update([
                'order_status' => $request->order_status,
                'admin_cancel_reason' => $request->admin_cancel_reason,
            ]);
        }
        else {
            $order->update([
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
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $order = $this->orders
        ->where('id', $request->order_id)
        ->first();
        if ($order->order_status != 'processing') {
            return response()->json([
                'faild' => 'Status must be processing'
            ], 400);
        }
        if (!is_numeric($request->order_number)) {
            $order->update([
                'delivery_id' => $request->delivery_id, 
                'order_status' => 'out_for_delivery',
            ]);
        }
        else{ 
            $order->update([
                'delivery_id' => $request->delivery_id,
                'order_number' => $request->order_number ?? $order->order_number,
                'order_status' => 'out_for_delivery',
            ]);
        }

        return response()->json([
            'success' => 'You select delivery success'
        ]);
    }

    public function user_details($id){
        // https://bcknd.food2go.online/admin/order/user_details/{user_id}
        $data = $this->user
        ->where('id', $id)
        ->withCount('orders')
        ->with(['orders' => function($query){
            $query->select('id', 'receipt', 'date', 'user_id', 'branch_id', 'amount',
            'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
            'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
            'notes', 'coupon_discount', 'order_number', 'payment_method_id', 'order_details',
            'status', 'points', 'rejected_reason', 'transaction_id');
        }, 'address'])
        ->get();

        return response()->json([
            'data' => $data
        ]);
    }

    public function order_log(Request $request){
        // /admin/order/log
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $log_order = $this->log_order
        ->where('order_id', $request->order_id)
        ->with('admin')
        ->get();

        return response()->json([
            'log_order' => $log_order,
        ]);
    }
}
