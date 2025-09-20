<?php

namespace App\Http\Controllers\api\cashier\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\TimeSittings;

class PendingOrderController extends Controller
{
    public function __construct(private Order $orders,
    private TimeSittings $TimeSittings){}

    public function get_pending_orders(Request $request){ 
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
            // }
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        }

        $all_orders = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id',
		'order_details')
        ->where('branch_id', $request->user()->branch_id)
        ->orderByDesc('id')
        ->whereBetween('created_at', [$start, $end])
        ->where('order_active', 0)
        ->get()
		->map(function($item){
			return [
				'id' => $item->id,
				'amount' => $item->amount,
				'order_details' => $item->order_details,
				'order_number' => $item->order_number,
				'notes' => $item->notes,
			];
		});

        return response()->json([
            'all_orders' => $all_orders
        ]);
    }

    public function get_order(Request $request, $id){
        $order = clone $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id', 'order_details')
        ->where('id', $id)
        ->where('order_active', 0) 
        ->first();
        // if(){

        // } 
        $this->orders
        ->where('id', $id)
        ->delete();

        return response()->json([
			'id' => $order->id,
			'amount' => $order->amount,
			'order_details' => $order->order_details,
			'order_number' => $order->order_number,
			'notes' => $order->notes,
		]);
    }
}
