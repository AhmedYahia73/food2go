<?php

namespace App\Http\Controllers\api\admin\report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

use App\Models\OrderDetail;
use App\Models\Branch;
use App\Models\TimeSittings;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchaseStock;

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
        ->groupBy('product_id')
        ->get()
        ->sortByDesc("product_count")
        ->load(["product"])
        ->map(function($item){
            return [
                "id" => $item->product_id,
                "product_name" => $item?->product?->name,
                "product_description" => $item?->product?->description,
				"count" => $item->product_count,
            ];
        });

        return response()->json([
            "products" => $products->values()
        ]);
    }
    public function filter_raise_product(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => ['exists:branches,id'], 
            'order_type' => ['in:delivery,take_away,dine_in'], 
            'from' => ['date'], 
            'to' => ['date'], 
            'limit' => ['numeric'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
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
            ->with(["product"])
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
                "count" => $item->product_count,
            ];
        });

        return response()->json([
            "products" => $products,
        ]);
    }
    public function low_product(Request $request)
    {
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
        ->groupBy('product_id')
        ->get()
        ->sortBy("product_count")
        ->load(["product", "order"])
        ->map(function($item){
            return [
                "id" => $item->product_id,
                "product_name" => $item?->product?->name,
                "product_description" => $item?->product?->description,
                "count" => $item->product_count,
            ];
        });

        return response()->json([
            "products" => $products->values()
        ]);
    }
    public function filter_low_product(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => ['exists:branches,id'], 
            'order_type' => ['in:delivery,take_away,dine_in'], 
            'from' => ['date'], 
            'to' => ['date'],
            'limit' => ['numeric'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
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
            ->with(["product"])
            ->groupBy("product_id")
            ->orderBy("product_count");
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
                "count" => $item->product_count,
            ];
        });

        return response()->json([
            "products" => $products,
        ]);
    }
    public function sales_product(Request $request)
    { 
//'pending','confirmed','processing','out_for_delivery','delivered','returned','faild_to_deliver','canceled','scheduled','refund'
        $online_pickup_order = Order::
        where("pos", 0)
        ->where("order_type", "take_away") 
        ->where("order_status", "delivered")
        ->sum("amount");
        $online_delivery_order = Order::
        where("pos", 0)
        ->where("order_type", "delivery")
        ->where("order_status", "delivered")
        ->sum("amount");
        $online_dine_in_order = Order::
        where("pos", 0)
        ->where("order_type", "dine_in")
        ->where("order_status", "delivered")
        ->sum("amount");
        $online_order = [
            "online_pickup_order" => $online_pickup_order,
            "online_delivery_order" => $online_delivery_order,
            "online_dine_in_order" => $online_dine_in_order,
            "total_online_order" => $online_pickup_order + $online_delivery_order + $online_dine_in_order,
        ];


      $offline_pickup_order = Order::
        where("pos", 1)
        ->where("order_type", "take_away") 
        ->where("order_status", "delivered")
        ->sum("amount");
        $offline_delivery_order = Order::
        where("pos", 1)
        ->where("order_type", "delivery")
        ->where("order_status", "delivered")
        ->sum("amount");
        $offline_dine_in_order = Order::
        where("pos", 1)
        ->where("order_type", "dine_in")
        ->where("order_status", "delivered")
        ->sum("amount");
        $pos_order = [
            "offline_pickup_order" => $offline_pickup_order,
            "offline_delivery_order" => $offline_delivery_order,
            "offline_dine_in_order" => $offline_dine_in_order,
            "total_offline_order" => $offline_pickup_order + $offline_delivery_order + $offline_dine_in_order,
        ];

        return response()->json([
            "pos_order" => $pos_order,
            "online_order" => $online_order,
        ]);
    }
    public function sales_product_filter(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'branch_id' => ['exists:branches,id'], 
            'order_type' => ['in:delivery,take_away,dine_in'], 
            'from' => ['date'], 
            'to' => ['date'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
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
//'pending','confirmed','processing','out_for_delivery','delivered','returned','faild_to_deliver','canceled','scheduled','refund'
        $online_pickup_order = Order::
        where("pos", 0)
        ->where("order_type", "take_away") 
        ->where("order_status", "delivered");
        $online_delivery_order = Order::
        where("pos", 0)
        ->where("order_type", "delivery")
        ->where("order_status", "delivered");
        $online_dine_in_order = Order::
        where("pos", 0)
        ->where("order_type", "dine_in")
        ->where("order_status", "delivered");

      $offline_pickup_order = Order::
        where("pos", 1)
        ->where("order_type", "take_away") 
        ->where("order_status", "delivered");
        $offline_delivery_order = Order::
        where("pos", 1)
        ->where("order_type", "delivery")
        ->where("order_status", "delivered");
        $offline_dine_in_order = Order::
        where("pos", 1)
        ->where("order_type", "dine_in")
        ->where("order_status", "delivered");
        if($request->from){
            $online_pickup_order = $online_pickup_order
            ->where("created_at", ">=", $start);
            $online_delivery_order = $online_delivery_order
            ->where("created_at", ">=", $start);
            $online_dine_in_order = $online_dine_in_order
            ->where("created_at", ">=", $start);

            $offline_pickup_order = $offline_pickup_order
            ->where("created_at", ">=", $start);
            $offline_delivery_order = $offline_delivery_order
            ->where("created_at", ">=", $start);
            $offline_dine_in_order = $offline_dine_in_order
            ->where("created_at", ">=", $start);
        }
        if($request->to){
            $online_pickup_order = $online_pickup_order
            ->where("created_at", "<=", $end);
            $online_delivery_order = $online_delivery_order
            ->where("created_at", "<=", $end);
            $online_dine_in_order = $online_dine_in_order
            ->where("created_at", "<=", $end);

            $offline_pickup_order = $offline_pickup_order
            ->where("created_at", "<=", $end);
            $offline_delivery_order = $offline_delivery_order
            ->where("created_at", "<=", $end);
            $offline_dine_in_order = $offline_dine_in_order
            ->where("created_at", "<=", $end);
        }

        if($request->branch_id){
            $online_pickup_order = $online_pickup_order
            ->where("branch_id", "<=", $request->branch_id);
            $online_delivery_order = $online_delivery_order
            ->where("branch_id", "<=", $request->branch_id);
            $online_dine_in_order = $online_dine_in_order
            ->where("branch_id", "<=", $request->branch_id);

            $offline_pickup_order = $offline_pickup_order
            ->where("branch_id", "<=", $request->branch_id);
            $offline_delivery_order = $offline_delivery_order
            ->where("branch_id", "<=", $request->branch_id);
            $offline_dine_in_order = $offline_dine_in_order
            ->where("branch_id", "<=", $request->branch_id);
        }

        if($request->order_type){
            $online_pickup_order = $online_pickup_order
            ->where("order_type", "<=", $request->order_type);
            $online_delivery_order = $online_delivery_order
            ->where("order_type", "<=", $request->order_type);
            $online_dine_in_order = $online_dine_in_order
            ->where("order_type", "<=", $request->order_type);

            $offline_pickup_order = $offline_pickup_order
            ->where("order_type", "<=", $request->order_type);
            $offline_delivery_order = $offline_delivery_order
            ->where("order_type", "<=", $request->order_type);
            $offline_dine_in_order = $offline_dine_in_order
            ->where("order_type", "<=", $request->order_type);
        }

        $pos_order = [
            "offline_pickup_order" => $offline_pickup_order->sum("amount"),
            "offline_delivery_order" => $offline_delivery_order->sum("amount"),
            "offline_dine_in_order" => $offline_dine_in_order->sum("amount"),
            "total_offline_order" => $offline_pickup_order->sum("amount") + $offline_delivery_order->sum("amount") + $offline_dine_in_order->sum("amount"),
        ];
        $online_order = [
            "online_pickup_order" => $online_pickup_order->sum("amount"),
            "online_delivery_order" => $online_delivery_order->sum("amount"),
            "online_dine_in_order" => $online_dine_in_order->sum("amount"),
            "total_online_order" => $online_pickup_order->sum("amount") + $online_delivery_order->sum("amount") + $online_dine_in_order->sum("amount"),
        ];

        return response()->json([
            "pos_order" => $pos_order,
            "online_order" => $online_order,
        ]);
    }
    public function purchase_product(Request $request)
    { 
//'pending','confirmed','processing','out_for_delivery','delivered','returned','faild_to_deliver','canceled','scheduled','refund'
        $purchase = Purchase::
        selectRaw("sum(total_coast) as amount, store_id")
        ->groupBy("store_id")
        ->get()
        ->load("store:id,name");

        return response()->json([
            "purchase" => $purchase,
        ]);
    }
    public function filter_purchase_product(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'store_id' => ['exists:purchase_stores,id'], 
            'from' => ['date'], 
            'to' => ['date'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $purchase = Purchase::
        selectRaw("sum(total_coast) as amount, store_id")
        ->with("store:id,name")
        ->groupBy("store_id");
        if($request->store_id){
            $purchase = $purchase->where("store_id", $request->store_id);
        }
        if($request->from){
            $purchase = $purchase->where("date", '>=', $request->from);
        }
        if($request->to){
            $purchase = $purchase->where("date", '<=',$request->to);
        }
        $purchase = $purchase
        ->get();

        return response()->json([
            "purchase" => $purchase,
        ]);
    }
    
    public function purchase_raise_product(Request $request)
    { 
//'pending','confirmed','processing','out_for_delivery','delivered','returned','faild_to_deliver','canceled','scheduled','refund'
        $products = Purchase::
        selectRaw("sum(quintity) as stock, product_id")
        ->groupBy("product_id")
        ->orderByDesc("stock")
        ->get()
        ->load("product:id,name,description");

        return response()->json([
            "products" => $products,
        ]);
    }
    public function filter_purchase_raise_product(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'store_id' => ['exists:purchase_stores,id'], 
            'from' => ['date'], 
            'to' => ['date'], 
            'limit' => ['numeric'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $products = Purchase::
        selectRaw("sum(quintity) as stock, product_id")
        ->groupBy("product_id")
        ->orderByDesc("stock");
  
        if($request->from){
            $products = $products->where("date", '>=', $request->from);
        }
        if($request->to){
            $products = $products
            ->where("date", "<=", $request->to);
        }
        if($request->store_id){
            $products = $products->where("store_id", $request->store_id);
        }
        if ($request->limit) {
            $products = $products->take($request->limit);
        }
        $products = $products
        ->get()
        ->load("product:id,name,description");


        return response()->json([
            "purchase" => $products,
        ]);
    }
    
    public function purchase_low_product(Request $request)
    { 
//'pending','confirmed','processing','out_for_delivery','delivered','returned','faild_to_deliver','canceled','scheduled','refund'
        $products = Purchase::
        selectRaw("sum(quintity) as stock, product_id")
        ->groupBy("product_id")
        ->orderByAsc("stock")
        ->get()
        ->load("product:id,name,description");

        return response()->json([
            "products" => $products,
        ]);
    }
    public function filter_purchase_low_product(Request $request)
    { 
        $validator = Validator::make($request->all(), [
            'store_id' => ['exists:purchase_stores,id'], 
            'from' => ['date'], 
            'to' => ['date'], 
            'limit' => ['numeric'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $products = Purchase::
        selectRaw("sum(quantity) as stock, product_id")
        ->groupBy("product_id")
        ->orderByAsc("stock");
        if($request->from){
            $products = $products
            ->where("date", ">=", $request->from);
        }
        if($request->to){
            $products = $products
            ->where("date", "<=", $request->to);
        }
        if($request->from){
            $products = $products
            ->where("date", ">=", $request->from);
        }  
 
        if($request->store_id){
            $purchase = $purchase->where("store_id", $request->store_id);
        }
        if($request->from){
            $purchase = $purchase->where("date", '>=', $request->from);
        }
        if($request->store_id){
            $purchase = $purchase->where("store_id", $request->store_id);
        }
        if ($request->limit) {
            $purchase = $purchase->take($request->limit);
        }
        $purchase = $purchase
        ->get()
        ->load("product:id,name,description");

        return response()->json([
            "purchase" => $purchase,
        ]);
    }
}
