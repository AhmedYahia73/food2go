<?php

namespace App\Http\Controllers\api\admin\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\Mail\CancelOrderMail;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\Delivery;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\LogOrder;
use App\Models\User;
use App\Models\TimeSittings; 

use App\trait\Recipe;

class OrderController extends Controller
{
    public function __construct(private Order $orders, private Delivery $deliveries, 
    private Branch $branches, private Setting $settings, private User $user,
    private LogOrder $log_order, private TimeSittings $TimeSittings){}
    use Recipe;

    public function transfer_branch(Request $request, $id){
        // admin/order/transfer_branch
        // keys => branch_id
        if ($request->user()->role == "admin") {
            $orders = $this->orders
            ->where('id', $id)
            ->update([
                'branch_id' => $request->branch_id,
                'operation_status' => 'pending',
                'admin_id' => null,
            ]);
        } else {
            $orders = $this->orders
            ->where('id', $id)
            ->where('branch_id', $request->user()->id)
            ->update([
                'branch_id' => $request->branch_id,
                'operation_status' => 'pending',
                'admin_id' => null,
            ]);
        }
        

        return response()->json([
            'success' => 'You update branch success'
        ]);
    }

    public function orders(Request $request){
    //     // https://bcknd.food2go.online/admin/order
      
    //     // settings 
    //     $time_sittings = $this->TimeSittings
    //     ->get();
    //     $from = $time_sittings->min('from');
    //     $hours = $time_sittings->max('hours');
    //     if (!empty($from)) {
    //         $from = date('Y-m-d') . ' ' . $from;
    //         $start = Carbon::parse($from);
    //         if ($start > date('H:i:s')) {
    //             $end = Carbon::parse($from)->addHours($hours)->subDay();
    //         }
    //         else{
    //             $end = Carbon::parse($from)->addHours(intval($hours));
    //         }
    //     } else {
    //         $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
    //         $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
    //     }
        

    //     $orders = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $pending = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'pending')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $confirmed = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'confirmed')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $processing = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'processing')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $out_for_delivery = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'out_for_delivery')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $delivered = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'delivered')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $returned = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'returned')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $faild_to_deliver = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'faild_to_deliver')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $canceled = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'canceled')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $scheduled = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'scheduled')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $refund = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'admin_id', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereBetween('created_at', [$start, $end])
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'refund')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();

    //     $all_data = [
    //         'orders' => $orders,
    //         'pending' => $pending,
    //         'confirmed' => $confirmed,
    //         'processing' => $processing,
    //         'out_for_delivery' => $out_for_delivery,
    //         'delivered' => $delivered,
    //         'returned' => $returned,
    //         'faild_to_deliver' => $faild_to_deliver,
    //         'canceled' => $canceled,
    //         'scheduled' => $scheduled,
    //         'refund' => $refund,
    //     ];

    //     // _____________________________________________________________
        
    //     $orders = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 'admin_id',
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $pending = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount', 'admin_id',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'pending')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $confirmed = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount', 'admin_id',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'confirmed')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $processing = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount', 'admin_id',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'processing')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $out_for_delivery = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount', 'admin_id',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'out_for_delivery')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $delivered = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount', 'admin_id',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'delivered')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $returned = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount', 'admin_id',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'returned')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $faild_to_deliver = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount', 'admin_id',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'faild_to_deliver')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $canceled = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount', 'admin_id',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'canceled')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $scheduled = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id', 'admin_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'scheduled')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get();
    //     $refund = $this->orders
    //     ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount',
    //     'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //     'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id', 'admin_id',
    //     'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //     'status', 'points', 'rejected_reason', 'transaction_id')
    //     ->where('pos', 0)
    //     ->whereDate('created_at', date('Y-m-d'))
    //     ->whereNull('captain_id')
    //     ->where(function($query) {
    //         $query->where('status', 1)
    //         ->orWhereNull('status');
    //     })
    //     ->where('order_status', 'refund')
    //     ->orderByDesc('id')
    //     ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //     'schedule', 'delivery'])
    //     ->get(); 
        
    //     $deliveries = $this->deliveries
    //     ->get();

    //     return response()->json([
    //         'orders' => $orders,
    //         'pending' => $pending,
    //         'confirmed' => $confirmed,
    //         'processing' => $processing,
    //         'out_for_delivery' => $out_for_delivery,
    //         'delivered' => $delivered,
    //         'returned' => $returned,
    //         'faild_to_deliver' => $faild_to_deliver,
    //         'refund' => $refund,
    //         'canceled' => $canceled,
    //         'scheduled' => $scheduled,
    //         'all_data' => $all_data,
    //         'deliveries' => $deliveries,
    //     ]);
        $time_sittings = $this->TimeSittings 
        ->get();
        if ($time_sittings->count() > 0) {
            $from = $time_sittings[0]->from;
            $end = date('Y-m-d') . ' ' . $time_sittings[$time_sittings->count() - 1]->from;
            $hours = $time_sittings[$time_sittings->count() - 1]->hours;
            $minutes = $time_sittings[$time_sittings->count() - 1]->minutes;
            $from = date('Y-m-d') . ' ' . $from;
            $start = Carbon::parse($from);
            $end = Carbon::parse($end);
			$end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
            if ($start >= $end) {
                $end = $end->addDay();
            }
			if($start >= now()){
                $start = $start->subDay();
			}

            // if ($start > $end) {
            //     $end = Carbon::parse($from)->addHours($hours)->subDay();
            // }
            // else{
            //     $end = Carbon::parse($from)->addHours(intval($hours));
            // } format('Y-m-d H:i:s')
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        } 
        $start = $start->subDay();
        if ($request->user()->role == "admin") {
            $orders = $this->orders
            ->select('id', 'order_number', 'created_at', 'sechedule_slot_id', 'admin_id', 'user_id', 'branch_id', 'amount', 'operation_status'
            ,'order_status', 'order_type',
            'delivery_id', 'address_id', 'source',
            'payment_method_id', 
            'status', 'points', 'rejected_reason', 'transaction_id')
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            }) 
            ->orderByDesc('id')
            ->with(['user:id,f_name,l_name,phone,image', 'branch:id,name', 'address' => function($query){
                $query->select('id', 'zone_id')
                ->with('zone:id,zone');
            }, 'admin:id,name,email,phone,image', 'payment_method:id,name,logo',
            'schedule:id,name', 'delivery'])
            ->get()
            ->map(function($item){
                return [ 
                    'id' => $item->id,
                    'order_number' => $item->order_number,
                    'created_at' => $item->created_at,
                    'amount' => $item->amount,
                    'operation_status' => $item->operation_status,
                    'order_type' => $item->order_type,
                    'order_status' => $item->order_status,
                    'source' => $item->source,
                    'status' => $item->status,
                    'points' => $item->points, 
                    'rejected_reason' => $item->rejected_reason,
                    'transaction_id' => $item->transaction_id,
                    'user' => [
                        'f_name' => $item?->user?->f_name,
                        'l_name' => $item?->user?->l_name,
                        'phone' => $item?->user?->phone],
                    'branch' => ['name' => $item?->branch?->name, ],
                    'address' => ['zone' => ['zone' => $item?->address?->zone?->zone]],
                    'admin' => ['name' => $item?->admin?->name,],
                    'payment_method' => ['name' => $item?->payment_method?->name],
                    'schedule' => ['name' => $item?->schedule?->name],
                    'delivery' => ['name' => $item?->delivery?->name], 
                ];
            });
        }
        else{
        $orders = $this->orders
            ->select('id', 'order_number', 'created_at', 'sechedule_slot_id', 'admin_id', 'user_id', 'branch_id', 'amount', 'operation_status'
            ,'order_status',
            'delivery_id', 'address_id', 'source',
            'payment_method_id', 'order_type',
            'status', 'points', 'rejected_reason', 'transaction_id')
            ->where('pos', 0)
            ->where("branch_id", $request->user()->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            }) 
            ->orderByDesc('id')
            ->with(['user:id,f_name,l_name,phone,image', 'branch:id,name', 'address' => function($query){
                $query->select('id', 'zone_id')
                ->with('zone:id,zone');
            }, 'admin:id,name,email,phone,image', 'payment_method:id,name,logo',
            'schedule:id,name', 'delivery'])
            ->get()
            ->map(function($item){
                return [ 
                    'id' => $item->id,
                    'order_number' => $item->order_number,
                    'created_at' => $item->created_at,
                    'amount' => $item->amount,
                    'operation_status' => $item->operation_status,
                    'order_type' => $item->order_type,
                    'order_status' => $item->order_status,
                    'source' => $item->source,
                    'status' => $item->status,
                    'points' => $item->points, 
                    'rejected_reason' => $item->rejected_reason,
                    'transaction_id' => $item->transaction_id,
                    'user' => [
                        'f_name' => $item?->user?->f_name,
                        'l_name' => $item?->user?->l_name,
                        'phone' => $item?->user?->phone],
                    'branch' => ['name' => $item?->branch?->name, ],
                    'address' => ['zone' => ['zone' => $item?->address?->zone?->zone]],
                    'admin' => ['name' => $item?->admin?->name,],
                    'payment_method' => ['name' => $item?->payment_method?->name],
                    'schedule' => ['name' => $item?->schedule?->name],
                    'delivery' => ['name' => $item?->delivery?->name], 
                ];
            });
        }
        $pending = $orders
        ->where('order_status', 'pending')
        ->values();
        $confirmed = $orders
        ->where('order_status', 'confirmed')
        ->values();
        $processing = $orders
        ->where('order_status', 'processing')
        ->values();
        $out_for_delivery = $orders
        ->where('order_status', 'out_for_delivery')
        ->values();
        $delivered = $orders
        ->where('order_status', 'delivered')
        ->values();
        $returned = $orders
        ->where('order_status', 'returned')
        ->values();
        $faild_to_deliver = $orders
        ->where('order_status', 'faild_to_deliver')
        ->values();
        $canceled = $orders
        ->where('order_status', 'canceled')
        ->values();
        $scheduled = $orders
        ->where('order_status', 'scheduled')
        ->values();
        $refund = $orders
        ->where('order_status', 'refund')
        ->values();
        $deliveries = $this->deliveries
        ->get();
        $branches = $this->branches
        ->where('status', 1)
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
            'deliveries' => $deliveries,
            'branches' => $branches,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
    }

    public function order_details(Request $request){
        // https://bcknd.food2go.online/admin/order 
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:pending,delivery,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled,refund',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $time_sittings = $this->TimeSittings 
        ->get();
        if ($time_sittings->count() > 0) {
            $from = $time_sittings[0]->from;
            $end = date('Y-m-d') . ' ' . $time_sittings[$time_sittings->count() - 1]->from;
            $hours = $time_sittings[$time_sittings->count() - 1]->hours;
            $minutes = $time_sittings[$time_sittings->count() - 1]->minutes;
            $from = date('Y-m-d') . ' ' . $from;
            $start = Carbon::parse($from);
            $end = Carbon::parse($end);
			$end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
            if ($start >= $end) {
                $end = $end->addDay();
            }
			if($start >= now()){
                $start = $start->subDay();
			}
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        } 
        $start = $start->subDay();
        $orders = $this->orders
        ->select('id', 'order_number', 'created_at', 'sechedule_slot_id', 'admin_id', 'user_id', 'branch_id', 'amount', 'operation_status'
        ,'order_status',
        'delivery_id', 'address_id', 'source',
        'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->whereBetween('created_at', [$start, $end])
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->orderByDesc('id')
        ->with(['user:id,f_name,l_name,phone,image', 'branch:id,name', 'address' => function($query){
			$query->select('id', 'zone_id')
			->with('zone:id,zone');
		}, 'admin:id,name,email,phone,image', 'payment_method:id,name,logo',
        'schedule:id,name', 'delivery'])
        ->where('order_status', $request->order_status)
        ->get()
		->map(function($item){
			return [ 
				'id' => $item->id,
				'order_number' => $item->order_number,
				'created_at' => $item->created_at,
				'amount' => $item->amount,
				'operation_status' => $item->operation_status,
				'order_status' => $item->order_status,
				'source' => $item->source,
				'status' => $item->status,
				'points' => $item->points, 
				'rejected_reason' => $item->rejected_reason,
				'transaction_id' => $item->transaction_id,
				'user' => [
                    'f_name' => $item?->user?->f_name,
                    'l_name' => $item?->user?->l_name,
                    'phone' => $item?->user?->phone],
				'branch' => ['name' => $item?->branch?->name, ],
				'address' => ['zone' => ['zone' => $item?->address?->zone?->zone]],
				'admin' => ['name' => $item?->admin?->name,],
				'payment_method' => ['name' => $item?->payment_method?->name],
				'schedule' => ['name' => $item?->schedule?->name],
				'delivery' => ['name' => $item?->delivery?->name], 
			];
		});

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function lists(Request $request){
        $deliveries = $this->deliveries
        ->select('id', 'f_name', 'l_name', 'phone')
        ->get();
        $branches = $this->branches
        ->select('id', 'name')
        ->where('status', 1)
        ->get();

        return response()->json([
            'deliveries' => $deliveries,
            'branches' => $branches,
        ]);
    }

    public function orders_count(Request $request){
    //     // https://bcknd.food2go.online/admin/order
        $time_sittings = $this->TimeSittings 
        ->get();
        if ($time_sittings->count() > 0) {
            $from = $time_sittings[0]->from;
            $end = date('Y-m-d') . ' ' . $time_sittings[$time_sittings->count() - 1]->from;
            $hours = $time_sittings[$time_sittings->count() - 1]->hours;
            $minutes = $time_sittings[$time_sittings->count() - 1]->minutes;
            $from = date('Y-m-d') . ' ' . $from;
            $start = Carbon::parse($from);
            $end = Carbon::parse($end);
			$end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
            if ($start >= $end) {
                $end = $end->addDay();
            }
			if($start >= now()){
                $start = $start->subDay();
			}

            // if ($start > $end) {
            //     $end = Carbon::parse($from)->addHours($hours)->subDay();
            // }
            // else{
            //     $end = Carbon::parse($from)->addHours(intval($hours));
            // } format('Y-m-d H:i:s')
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        } 
        $start = $start->subDay();
        if ($request->user()->role == "admin") {
            $orders = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })  
            ->count();
            $pending = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'pending')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })  
            ->count();
            $confirmed = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'confirmed')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })  
            ->count();
            $processing = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'processing')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })  
            ->count();
            $out_for_delivery = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'out_for_delivery')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })  
            ->count();
            $delivered = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'delivered')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->count();
            $returned = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'returned')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->count();
            $faild_to_deliver = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'faild_to_deliver')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->count();
            $canceled = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'canceled')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->count();
            $scheduled = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'scheduled')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->count();
            $refund = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'refund')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->count(); 
        }
        else{
            $orders = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)  
            ->count();
            $pending = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'pending')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)  
            ->count();
            $confirmed = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'confirmed')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)  
            ->count();
            $processing = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'processing')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)  
            ->count();
            $out_for_delivery = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'out_for_delivery')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)  
            ->count();
            $delivered = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'delivered')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)
            ->count();
            $returned = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'returned')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)
            ->count();
            $faild_to_deliver = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'faild_to_deliver')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)
            ->count();
            $canceled = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'canceled')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)
            ->count();
            $scheduled = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'scheduled')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)
            ->count();
            $refund = $this->orders
            ->where('pos', 0)
            ->whereBetween('created_at', [$start, $end])
            ->whereNull('captain_id')
            ->where('order_status', 'refund')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->where("branch_id", $request->user()->id)
            ->count(); 
        }

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
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
    }

    public function count_orders(Request $request){
        // https://bcknd.food2go.online/admin/order/count
        
        if ($request->user()->role == "admin") {
            $orders = $this->orders 
            ->where('pos', 0)
            ->whereNull('captain_id')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->orderByDesc('id')
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->count();
        }
        else{
            $orders = $this->orders 
            ->where('pos', 0)
            ->whereNull('captain_id')
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->orderByDesc('id')
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
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
            ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
            'schedule', 'delivery'])
            ->where("branch_id", $request->user()->id)
            ->count();
        }

        return response()->json([
            'orders' => $orders,
            'pending' => $pending,
            // 'confirmed' => $confirmed,
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

    // public function orders_data(Request $request){
    //     // https://bcknd.food2go.online/admin/order/data
    //     $validator = Validator::make($request->all(), [
    //         'order_status' => 'required|in:all,pending,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled,refund',
    //     ]);
    //     if ($validator->fails()) { // if Validate Make Error Return Message Error
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ],400);
    //     }
    //     if ($request->order_status == 'all') {
    //         $orders = $this->orders
    //         ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount',
    //         'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //         'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //         'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //         'status', 'points', 'rejected_reason', 'transaction_id')
    //         ->where('pos', 0)
    //     ->whereNull('captain_id')
    //         ->where(function($query) {
    //             $query->where('status', 1)
    //             ->orWhereNull('status');
    //         })
    //         ->orderByDesc('id')
    //         ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //         'schedule', 'delivery'])
    //         ->get();
    //     } 
    //     else {
    //         $orders = $this->orders
    //         ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount',
    //         'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //         'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //         'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //         'status', 'points', 'rejected_reason', 'transaction_id')
    //         ->where('pos', 0)
    //     ->whereNull('captain_id')
    //         ->where(function($query) {
    //             $query->where('status', 1)
    //             ->orWhereNull('status');
    //         })
    //         ->where('order_status', $request->order_status)
    //         ->orderByDesc('id')
    //         ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //         'schedule', 'delivery'])
    //         ->get();
    //     }

    //     return response()->json([
    //         'orders' => $orders
    //     ]);
    // }

    public function notification(Request $request){
        // https://bcknd.food2go.online/admin/order/notification
        // Key
        // orders
        $total = 0;
        if ($request->user()->role == "admin") {
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
        }
        else{
            if ($request->orders) {
                $old_orders = $request->orders;
                $new_orders = $this->orders
                ->where('pos', 0)
                ->whereNull('captain_id')
                ->where("branch_id", $request->user()->id)
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
            ->where("branch_id", $request->user()->id)
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            })
            ->orderByDesc('id')
            ->limit($total)->pluck('id');
        }

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

    // public function order_filter(Request $request){
    //     // https://bcknd.food2go.online/admin/order/filter
    //     // Key
    //     // from, to, branch_id, type
    //     $validator = Validator::make($request->all(), [ 
    //         'type' => 'in:all,pending,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled,refund'
    //     ]);
    //     if ($validator->fails()) { // if Validate Make Error Return Message Error
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ],400);
    //     }

    //     if ($request->type) {
    //         if ($request->type == 'all') {
    //             $orders = $this->orders
    //             ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount',
    //             'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //             'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //             'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //             'status', 'points', 'rejected_reason', 'transaction_id')
    //             ->where('pos', 0)
    //     ->whereNull('captain_id')
    //             ->where('status', '!=', 2)
    //             ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //             'schedule', 'delivery'])
    //             ->orderBy('created_at')
    //             ->get();
    //         } else {
    //             $orders = $this->orders
    //             ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount',
    //             'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //             'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //             'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //             'status', 'points', 'rejected_reason', 'transaction_id')
    //             ->where('pos', 0)
    //     ->whereNull('captain_id')
    //             ->where('status', '!=', 2)
    //             ->where('order_status', $request->type)
    //             ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //             'schedule', 'delivery'])
    //             ->orderBy('created_at')
    //             ->get();
    //         }
    //     }
    //     else{
    //         $orders = $this->orders
    //         ->select('id', 'date', 'sechedule_slot_id', 'operation_status', 'user_id', 'branch_id', 'amount',
    //         'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
    //         'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
    //         'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
    //         'status', 'points', 'rejected_reason', 'transaction_id')
    //         ->where('pos', 0)
    //     ->whereNull('captain_id')
    //         ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
    //         'schedule', 'delivery'])
    //         ->orderBy('created_at')
    //         ->get();
    //     }
        
    //     if ($request->branch_id && $request->branch_id != 0) {
    //         $orders = $orders
    //         ->where('branch_id', $request->branch_id);
    //     }
    //     if ($request->from) {
    //         $orders = $orders
    //         ->where('order_date', '>=', $request->from);
    //     }
    //     if ($request->to) {
    //         $orders = $orders
    //         ->where('order_date', '<=', $request->to);
    //     }

    //     return response()->json([
    //         'orders' => array_values($orders->toArray())
    //     ]);
    // }

    public function order($id){
        // https://bcknd.food2go.online/admin/order/order/{id}
        $order = $this->orders
        ->select('id', 'receipt', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id', 'source',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 'order_details',
        'status', 'points', 'rejected_reason', 'transaction_id', 'customer_cancel_reason', 
        'admin_cancel_reason', 'sechedule_slot_id')
        ->with(['user:id,f_name,l_name,phone,phone_2,image,email', 
        'branch:id,name', 'delivery', 'payment_method:id,name,logo',
         'address.zone', 'admin:id,name,email,phone,image', 
        'schedule'])
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->find($id);
        $order->makeHidden('order_details_data');
        $order_details = collect($order->order_details);
        foreach ($order_details as $item) {
            foreach ($item->product as $element) {
                $total = collect($item->variations)->pluck('options')->flatten(1)
                ->where('product_id', $element->product->id)->sum('price');
                $element->product->price += $total;
            }
        }
        $order->order_details = $order_details;
        try {
            $order->user->count_orders = $this->orders->where('user_id', $order->user_id)->count();
        } 
        catch (\Throwable $th) {
            $order->user = collect([]);
            $order->user->count_orders = 0;
        }
        if (!empty($order->branch)) {
            $order->branch->count_orders = $this->orders->where('branch_id', $order->branch_id)->count();
        }
        if (!empty($order->delivery_id)) {
            $order->delivery->count_orders = $this->orders
            ->where('delivery_id', $order->delivery_id)
            ->count();
        }
        $deliveries = $this->deliveries
        ->select('id', 'f_name', 'l_name')
        ->get();
        $order_status = ['pending', 'processing', 'out_for_delivery',
        'delivered' ,'canceled', 'confirmed', 'scheduled', 'returned' ,
        'faild_to_deliver', 'refund'];
        $preparing_time = $order->branch->food_preparion_time ?? '00:30';
        // if (empty($preparing_time)) {
        $time_parts = explode(':', $preparing_time);

        // _________________________________________
        
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
        $time_to_add = $delivery_time->setting;
        list($order_hours, $order_minutes, $order_seconds) = explode(':', $time_to_add);
        // Get hours, minutes, and seconds
        $hours = $time_parts[0];
        $minutes = $time_parts[1]; 
        $order_seconds = 0;
        $hours = (int)$hours;
        $minutes = (int)$minutes;
        
        if($order->order_type == 'delivery'){
            // Ensure that $hours, $minutes, and $seconds are integers
            $hours = (int)$hours + (int)$order_hours;
            $minutes = (int)$minutes + (int)$order_minutes;
            $order_seconds = '00';
        }
        $hours += intval($minutes / 60);
        $minutes = $minutes % 60;
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
        $log_order = $this->log_order
        ->with(['admin:id,name'])
        ->where('order_id', $id)
        ->get();
        $branches = $this->branches
        ->select('name', 'id')
        ->where('status', 1)
        ->get();
        try {
            if($order?->user?->orders){ 
                $order->user->makeHidden("orders");
				$order->user;
            } 
			if($order?->branch){
                unset($order->branch);
				$order->branch;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return response()->json([
            'order' => $order,
            'deliveries' => $deliveries,
            'order_status' => $order_status,
            'preparing_time' => $preparing_arr,
            'log_order' => $log_order,
            'branches' => $branches,
        ]);
    }

    public function invoice($id){
        // https://bcknd.food2go.online/admin/order/invoice/{id}
        $order = $this->orders
        ->with(['user', 'address.zone.city', 'admin:id,name,email,phone,image', 'branch', 'delivery'])
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
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
            'order_status' => 'required|in:delivery,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled,refund',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        if ($request->user()->role == "admin") {
            $order = $this->orders
            ->where('id', $id)
            ->first();
        }
        else{
            $order = $this->orders
            ->where('id', $id)
            ->where("branch_id", $request->user()->id)
            ->first();
        }
        $old_status = $order->order_status;
        if (empty($order)) {
            return response()->json([
                'errors' => 'order is not found'
            ], 400);
        }
        if ($request->order_status == 'delivered' || $request->order_status == 'returned'
        || $request->order_status == 'faild_to_deliver'|| $request->order_status == 'refund'
        || $request->order_status == 'canceled') {
            $order->update([
                'operation_status' => 'closed',
            ]);
        }
       if ($order->operation_status == 'pending') {
            $order->update([
                'admin_id' => $request->user()->id,
                'operation_status' => 'opened',
            ]);
        }
        else{
            $arr =  ['pending','processing','confirmed','out_for_delivery','delivered','returned'
            ,'faild_to_deliver','canceled','scheduled','refund'];
            $new_index = array_search($request->order_status, $arr);
            $old_index = array_search($order->order_status, $arr);
            $user = $request->user();
            $roles = $user?->user_positions?->roles?->where('role', 'Order')->pluck('action')->values();
            $hasAllPermission = $roles->contains('all');
            $hasBackStatus = $roles->contains('back_status');
            $hasStatusPermission = $roles->contains('change_status');
            $hasRequiredPermission = $hasAllPermission || $hasStatusPermission;
            if (!$hasAllPermission && !$hasBackStatus && $new_index < $old_index) {
                return response()->json([
                    'errors' => "You can't back by status"
                ], 400);
            }

            if ($order->admin_id !== $user->id && !$hasRequiredPermission) {
                return response()->json([
                    'errors' => "You can't change status"
                ], 400);
            }
        }

        if($old_status == "pending"){
            $order_details = $order->order_details;
            $products = [];
            foreach ($order_details as $item) { 
                $product_item = $item->product[0]; 
                $products[] = [
                    "id" => $product_item->product->id,
                    "count" => $product_item->count,
                ];
            }
            $errors = $this->pull_recipe($products, $order->branch_id); 
            if(!$errors['success']){
                return response()->json([
                    "errors" => $errors->msg
                ], 400);
            }
        }

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
                    'errors' => $validator->errors(),
                ],400);
            }
            $data = [
                'name' => $order?->user?->name,
                'reason' => $request->admin_cancel_reason,
            ];
            Mail::to($order->user->email)->send(new CancelOrderMail($data));
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
        $this->log_order
        ->create([
            'order_id' => $id,
            'admin_id' => $request->user()->id,
            'from_status' => $old_status,
            'to_status' => $request->order_status,
        ]); 

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
                'errors' => $validator->errors(),
            ],400);
        }
        $order = $this->orders
        ->where('id', $request->order_id)
        ->first();
        if ($order->order_status != 'processing' && $order->order_status != 'out_for_delivery'
         && $order->order_status != 'confirmed') {
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
                'errors' => $validator->errors(),
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

    public function order_filter_date(Request $request){
        // https://sultanayubbcknd.food2go.online/admin/order_filter_date
        // date, date_to, branch_id, 
        // type => all,pending,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled,refund,
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'date_to' => 'required|date',
            'branch_id' => 'exists:branches,id',
            'type' => 'required|in:all,pending,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled,refund'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        // _______________________________________
        
        $time_sittings = $this->TimeSittings 
        ->get();
        if ($time_sittings->count() > 0) {
            $from_time = $time_sittings[0]->from;
            $date_to = $request->date_to; 
            $end = $date_to . ' ' . $time_sittings[$time_sittings->count() - 1]->from;
            $hours = $time_sittings[$time_sittings->count() - 1]->hours;
            $minutes = $time_sittings[$time_sittings->count() - 1]->minutes;
            $from = $request->date . ' ' . $from_time;
            $start = Carbon::parse($from);
            $end = Carbon::parse($end);
			$end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes); 
            if ($start >= $end) {
                $end = $end->addDay();
            } 
			if($start >= now()){
                $start = $start->subDay();
			}
            // if ($start > $end) {
            //     $end = Carbon::parse($from)->addHours($hours)->subDay();
            // }
            // else{
            //     $end = Carbon::parse($from)->addHours(intval($hours));
            // }
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        }
        // ___________________________________________________
        // settings     
            // if ($start > date('H:i:s')) {
            //     $end = Carbon::parse($date_to)->addHours($hours)->subDay();
            // }
            // else{
            //     $end = Carbon::parse($date_to)->addHours(intval($hours));
            // } 
        
        $orders = $this->orders
        ->whereBetween('created_at', [$start, $end])
        ->where('pos', 0)
        ->with(['user', 'branch', 'address.zone', 'admin:id,name,email,phone,image', 'payment_method',
        'schedule', 'delivery'])
        ->get();
        if ($request->type != 'all') {
            $orders = $orders->where('order_status', $request->type)->values();
        }
        if ($request->branch_id) {
            $orders = $orders->where('branch_id', $request->branch_id)->values();
        }

        return response()->json([
            'orders' => $orders
        ]);
    }
}
