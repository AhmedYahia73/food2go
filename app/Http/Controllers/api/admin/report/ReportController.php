<?php

namespace App\Http\Controllers\api\admin\report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\OrderDetail;
use App\Models\Branch;
use App\Models\TimeSittings;

class ReportController extends Controller
{
    public function view_raise_product(){
        $products = OrderDetail::
        selectRaw("product_id, sum(count) as product_count")
        ->whereNull('exclude_id')
        ->whereNull('addon_id')
        ->whereNull('offer_id')
        ->whereNull('extra_id')
        ->whereNull('variation_id')
        ->whereNull('option_id')
        ->whereNull('deal_id')
        ->whereHas("product")
        ->whereHas("order")
        ->get()
        ->sortByDesc("product_count")
        ->load(["product", "order"])
        ->map(function($item){
            return [
                "id" => $item->product_id,
                "product_name" => $item?->product?->name,
                "product_description" => $item?->product?->description,
                "branch" => $item?->order?->branch?->name,
                "order_type" => $item?->order?->order_type,
                "pos" => $item?->order?->pos,
                "date" => $item?->created_at
            ];
        });

        return response()->json([
            "products" => $products
        ]);
    }

    public function lists(Request $request){
        $branches = Branch::
        select("id", "name")
        ->where("status", 1)
        ->get();
        $order_type = [
            "delivery",
            "take_away",
            "dine_in",
        ];

        return response()->json([
            "branches" => $branches,
            "order_type" => $order_type,
        ]);
    }
    public function filter_raise_product(Request $request)
    {
        $time_sittings = TimeSittings::get();

        if ($time_sittings->count() > 0) {
            $first = $time_sittings->first();
            $last = $time_sittings->last();

            $fromDate = $request->from ?? date("Y-m-d");
            $toDate = $request->to ?? date("Y-m-d");

            $from = $fromDate . ' ' . $first->from;
            $end = $toDate . ' ' . $last->from;

            $start = Carbon::parse($from);
            $end = Carbon::parse($end)->addHours($last->hours)->addMinutes($last->minutes);

            if ($start >= $end) {
                $end = $end->addDay();
            }

            if ($start >= now()) {
                $start = $start->subDay();
            }
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        }

        // 🟢 استعلام قاعدة البيانات
        $products = OrderDetail::selectRaw("product_id, SUM(count) as product_count")
            ->whereNull('exclude_id')
            ->whereNull('addon_id')
            ->whereNull('offer_id')
            ->whereNull('extra_id')
            ->whereNull('variation_id')
            ->whereNull('option_id')
            ->whereNull('deal_id')
            ->whereHas("product")
            ->whereHas("order", function ($query) use ($request) {
                if ($request->branch_id) {
                    $query->where("branch_id", $request->branch_id);
                }
                if ($request->order_type) {
                    $query->where("order_type", $request->order_type);
                }
            })
            ->whereBetween("created_at", [$start, $end]) // ✅ فلترة التاريخ هنا
            ->with(["product", "order.branch"])
            ->groupBy("product_id")
            ->orderByDesc("product_count");
            if($request->from){
                $products = $products->where("created_at", ">=", $start);
            }
            if($request->to){
                $products = $products->where("created_at", "<=", $end);
            }
            $products = $products->get();

        if ($request->limit) {
            $products = $products->take($request->limit);
        }

        $products = $products->map(function ($item) {
            return [
                "id" => $item->product_id,
                "product_name" => $item?->product?->name,
                "product_description" => $item?->product?->description,
                "branch" => $item?->order?->branch?->name,
                "order_type" => $item?->order?->order_type,
                "pos" => $item?->order?->pos,
                "date" => optional($item?->order)->created_at?->format('Y-m-d H:i:s'),
                "count" => $item->product_count,
            ];
        });

        return response()->json([
            "products" => $products,
            "period" => [
                "from" => $start->toDateTimeString(),
                "to" => $end->toDateTimeString(),
            ],
        ]);
    }
}
