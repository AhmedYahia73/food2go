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
     
        $branch_id = $request->user()->branch_id ?? null;
        $branch_sittings = $branch_id ? TimeSittings::where('branch_id', $branch_id)->get() : collect();
        $time_sittings = $branch_sittings->count() > 0 ? $branch_sittings : TimeSittings::get();

        $items = [];
        $count = 0;
        $to = isset($time_sittings[0]) ? $time_sittings[0] : null; 
        $from = isset($time_sittings[0]) ? $time_sittings[0] : null;
        foreach ($time_sittings as $item) {
            $items[$item->branch_id][] = $item;
        }
        foreach ($items as $item) {
            if(count($item) > $count || (count($item) == $count && $item[count($item) - 1]->from > $to->from) ){
                $count = count($item);
                $to = $item[$count - 1];
            } 
            if($from && $from->from > $item[0]->from){
                $from = $item[0];
            }
        }
        if ($time_sittings->count() > 0 && $from && $to) {
            $from_val = $from->from;
            $end = date("Y-m-d") . ' ' . $to->from;
            $hours = $to->hours;
            $minutes = $to->minutes;
            $from_val = date("Y-m-d") . ' ' . $from_val;
            $start = Carbon::parse($from_val);
            $end = Carbon::parse($end);
            $end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
            if ($start >= $end) {
                $end = $end->addDay();
            }
            // Extend closing by 1 hour (after end by one hour)
            $end = $end->copy()->addHour();

            if ($start >= now()) {
                $start = $start->subDay();
                $end = $end->subDay();
            } 
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59')->addHour();
            if ($start >= now()) {
                $start = $start->subDay();
                $end = $end->subDay();
            }
        } 

        $all_orders = $this->orders
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id',
		'order_details', 'take_away_status', 'delivery_status')
        ->where('branch_id', $request->user()->branch_id)
        ->orderByDesc("created_at")
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

            $orderDetails = $item->order_details;
            if (is_string($orderDetails)) {
                $decoded = json_decode($orderDetails, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $orderDetails = $decoded;
                }
            }

            $orderNumber = ($item->order_number && $item->order_number !== 'null') ? $item->order_number : $item->id;
            $customerName = null;
            $phone = null;
            if ($item->user_id && $item->user_id !== 'null') {
                $u = \App\Models\User::find($item->user_id);
                if ($u) {
                    $customerName = trim(($u->f_name ?? '') . ' ' . ($u->l_name ?? '')) ?: ($u->name ?? null);
                    $phone = $u->phone ?? null;
                }
            }

			return [
				'id' => $item->id,
				'amount' => $item->amount,
				'order_details' => $orderDetails,
				'order_number' => $orderNumber,
				'notes' => ($item->notes && $item->notes !== 'null') ? $item->notes : null,
				'status' => $status,
                'created_at' => $item->created_at ? $item->created_at->toIso8601String() : null,
                'date' => $item->date ?: ($item->created_at ? $item->created_at->toDateString() : null),
                'customer_name' => $customerName,
                'phone' => $phone,
                'prepare_order' => $item->prepare_order ?? 0,
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
