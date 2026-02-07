<?php

namespace App\Http\Controllers\api\cashier\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\trait\OrderFormat;
use App\Models\Order;
use App\Models\TimeSittings;

class PendingOrderController extends Controller
{
    use OrderFormat;

    public function __construct(private Order $orders,
    private TimeSittings $TimeSittings){}

    public function get_pending_orders(Request $request){ 
     
        $time_sittings = TimeSittings::
        get();

        $items = [];
        $count = 0;
        $to = isset($time_sittings[0]) ? $time_sittings[0] : 0; 
        $from = isset($time_sittings[0]) ? $time_sittings[0] : 0;
        foreach ($time_sittings as $item) {
            $items[$item->branch_id][] = $item;
        }
        foreach ($items as $item) {
            if(count($item) > $count || (count($item) == $count && $item[count($item) - 1]->from > $to->from) ){
                $count = count($item);
                $to = $item[$count - 1];
            } 
            if($from->from > $item[0]->from){
                $from = $item[0];
            }
        }
        if ($time_sittings->count() > 0) {
            $from = $from->from;
            $end = date("Y-m-d") . ' ' . $to->from;
            $hours = $to->hours;
            $minutes = $to->minutes;
            $from = date("Y-m-d") . ' ' . $from;
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

        $all_orders = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id',
		'order_details', 'take_away_status', 'delivery_status')
        ->where('branch_id', $request->user()->branch_id)
        ->orderByDesc('id')
        ->whereBetween('created_at', [$start, $end])
        ->where('order_active', 0)
        ->get()
		->map(function($item){
            $status = "pickup";
            if($item->order_type == "take_away"){
                $status = $item->take_away_status;
            }
            elseif($item->order_type == "delivery"){
                $status = $item->delivery_status;
            } 
			return [
				'id' => $item->id,
				'amount' => $item->amount,
				'order_details' => $item->order_details,
				'order_number' => $item->order_number,
				'notes' => $item->notes,
				'status' => $status,
			];
		});

        return response()->json([
            'all_orders' => $all_orders
        ]);
    }

    public function get_order(Request $request, $id){
        $locale = $request->locale ?? "en";

        $order = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id', 'order_details')
        ->where('id', $id)
        ->where('order_active', 0) 
        ->first();
        if(empty($order)){
            return response()->json([
                'errors' => 'id is not found'
            ], 400);
        } 
        $order_item = $this->main_order_details_format($id, $locale);
        $this->orders
        ->where('id', $id)
        ->delete();

        return response()->json([
			'id' => $order->id,
			'amount' => $order->amount,
			'order' => $order_item['order_details'],
			'order_details' => $order->order_details,
			'order_number' => $order->order_number,
			'notes' => $order->notes,
		]);
    }
}
