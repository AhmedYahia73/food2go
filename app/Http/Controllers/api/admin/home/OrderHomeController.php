<?php

namespace App\Http\Controllers\api\admin\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\TimeSittings;

class OrderHomeController extends Controller
{
    public function orders_count(Request $request){
        // Verify header password
        if ($request->header('password') !== 'Food2goRest$@#20203030') {
            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'Invalid or missing password header'
            ], 401);
        }

        $validator = Validator::make($request->all(), [ 
            "date" => "nullable|date",
            "date_to" => "nullable|date",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        $from_date = $request->date ?? $request->from ?? date("Y-m-d");
        $to_date = $request->date_to ?? $request->to ?? date("Y-m-d");
        $time_sittings = TimeSittings::get();
        $items = [];
        $count = 0;
        $to = isset($time_sittings[0]) ? $time_sittings[0] : null; 
        $from = isset($time_sittings[0]) ? $time_sittings[0] : null;

        foreach ($time_sittings as $item) {
            $items[$item->branch_id][] = $item;
        }

        foreach ($items as $item) {
            if (count($item) > $count || (count($item) == $count && is_object($to) && $item[count($item) - 1]->from > $to->from)) {
                $count = count($item);
                $to = $item[$count - 1];
            } 
            if (is_object($from) && $from->from > $item[0]->from) {
                $from = $item[0];
            }
        }

        if ($time_sittings->count() > 0 && is_object($from) && is_object($to)) {
            $from_time = $from->from;
            $end_time = $to->from;
            $hours = intval($to->hours ?? 0);
            $minutes = intval($to->minutes ?? 0);
            $start = Carbon::parse($from_date . ' ' . $from_time);
            $end = Carbon::parse($to_date . ' ' . $end_time)->addHours($hours)->addMinutes($minutes);
            if ($start >= $end) {
                $end = $end->addDay();
            }
            if ($start >= now()) {
                $start = $start->subDay();
            }
        } else {
            $start = Carbon::parse($from_date . ' 00:00:00');
            $end = Carbon::parse($to_date . ' 23:59:59');
        } 

        $counts = Order::
        select('order_status', DB::raw('count(*) as total'))
        ->where('pos', 0)
        ->whereBetween('created_at', [$start, $end])
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->groupBy('order_status')
        ->pluck('total', 'order_status')
        ->toArray(); 

        $canceled = $counts['canceled'] ?? 0;
        $cancel_customer = Order::
        where('pos', 0)
        ->where('order_status', 'canceled')
        ->whereBetween('created_at', [$start, $end])
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->whereNotNull("customer_cancel_reason")
        ->count(); 
        $canceled_by_admin = max(0, $canceled - $cancel_customer);

        $orders = array_sum($counts);
        $pending = $counts['pending'] ?? 0;
        $confirmed = $counts['confirmed'] ?? 0;
        $processing = $counts['processing'] ?? 0;
        $out_for_delivery = $counts['out_for_delivery'] ?? 0;
        $delivered = $counts['delivered'] ?? 0;
        $returned = $counts['returned'] ?? 0;
        $faild_to_deliver = $counts['faild_to_deliver'] ?? 0;
        $scheduled = $counts['scheduled'] ?? 0;
        $refund = $counts['refund'] ?? 0;

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
            'cancel_customer' => $cancel_customer, 
            'canceled_by_admin' => $canceled_by_admin,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);
    }
}
