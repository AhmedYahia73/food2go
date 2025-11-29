<?php

namespace App\Http\Controllers\api\admin\report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

use App\Models\CashierMan;
use App\Models\Cashier; 
use App\Models\OrderFinancial;
use App\Models\FinantiolAcounting;
use App\Models\Branch;
use App\Models\TimeSittings;
use App\Models\Order;
use App\Models\Purchase;
use App\Models\PurchaseStock;
use App\Models\Expense;
use App\Models\FinancialHistory;
use App\Models\CashierShift;

class ReportController extends Controller
{
    public function __construct(private Expense $expenses,
    private Order $orders, private CashierShift $cashier_shift){}

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

    public function lists_report(Request $request){
        $cashier_man = CashierMan::
        select("id", "user_name")
        ->get();
        $cashier = Cashier::
        select("id", "name")
        ->get();
        $financial_account = FinantiolAcounting::
        select("id", "name")
        ->get(); 
        $branches = Branch::
        select("id", "name")
        ->get();  

        return response()->json([
            "cashier_man" => $cashier_man,
            "cashier" => $cashier,
            "financial_account" => $financial_account,
            "branches" => $branches,
        ]);
    }

    public function orders_report(Request $request){
        $validator = Validator::make($request->all(), [
            'from' => ['date'],
            'to' => ['date'],
            'cashier_id' => ['exists:cashiers,id'],
            'branch_id' => ['exists:branches,id'],
            'cashier_man_id' => ['exists:cashier_men,id'],
            'financial_id' => ['exists:finantiol_acountings,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        // Order
        $orders = Order::
        orderByDesc("id");

        if($request->from || $request->to){ 
            $time_sittings = TimeSittings:: 
            get();
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
                $start = Carbon::parse(date('Y-m-d') . ' ' . ' 00:00:00');
                $end = Carbon::parse(date('Y-m-d') . ' ' . ' 23:59:59');
            } 
            $start = Carbon::parse($request->from . ' ' . $start->format('H:i:s'));
            $end = Carbon::parse($request->to . ' ' . $end->format('H:i:s'));
            $orders = $orders
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
        }
        if($request->cashier_id){
            $orders = $orders
            ->where("cashier_id", $request->cashier_id);
        }
        if($request->branch_id){
            $orders = $orders
            ->where("branch_id", $request->branch_id);
        }
        if($request->cashier_man_id){
            $orders = $orders
            ->where("cashier_man_id", $request->cashier_man_id);
        }
        if($request->financial_id){
            $orders = $orders
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });
        }
        
        $orders = $orders
        ->with(['user:id,f_name,l_name,phone,image', 'branch:id,name', 'address' => function($query){
            $query->select('id', 'zone_id')
            ->with('zone:id,zone');
        }, 'admin:id,name,email,phone,image', 'payment_method:id,name,logo',
        'schedule:id,name', 'delivery', "financial_accountigs:id,name"])
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
            "type" => $item->pos ? "point of sale" : "online order",
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
            'financial_accountigs' => $item?->financial_accountigs, 
        ];
    });

        return response()->json([
            'orders' => $orders
        ]);
    }
    
    public function financial_report(Request $request){
        $validator = Validator::make($request->all(), [
            'from' => ['date'],
            'to' => ['date'],
            'cashier_id' => ['exists:cashiers,id'],
            'branch_id' => ['exists:branches,id'],
            'cashier_man_id' => ['exists:cashier_men,id'],
            'financial_id' => ['exists:finantiol_acountings,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        // Order
        $orders = Order::
        select("id");
        $expenses = $this->expenses;
        $start = Carbon::parse('1111-01-01');
        $end = now();
        $end = Carbon::parse($end);
        if($request->from || $request->to){
            
            $time_sittings = TimeSittings:: 
            get();
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
                $start = Carbon::parse(date('Y-m-d') . ' ' . ' 00:00:00');
                $end = Carbon::parse(date('Y-m-d') . ' ' . ' 23:59:59');
            } 
            $start = Carbon::parse($request->from . ' ' . $start->format('H:i:s'));
            $end = Carbon::parse($request->to . ' ' . $end->format('H:i:s'));

            $orders = $orders
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
            $expenses = $expenses
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
        }
        if($request->cashier_id){
            $orders = $orders
            ->where("cashier_id", $request->cashier_id);
            $expenses = $expenses
            ->where("cashier_id", $request->cashier_id);
        }
        if($request->branch_id){
            $orders = $orders
            ->where("branch_id", $request->branch_id);
            $expenses = $expenses
            ->where("branch_id", $request->branch_id);
        }
        if($request->cashier_man_id){
            $orders = $orders
            ->where("cashier_man_id", $request->cashier_man_id);
            $expenses = $expenses
            ->where("cahier_man_id", $request->cashier_man_id);
        }
        if($request->financial_id){
            $orders = $orders
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });
            $expenses = $expenses
            ->where("financial_account_id", $request->financial_id);
        }
        
        $orders = $orders 
        ->get()
        ?->pluck("id")?->toArray() ?? [];
        $financial = FinancialHistory::
        selectRaw("SUM(amount) as total");
        $start_balance = FinantiolAcounting::
        where("id", $request->financial_id)
        ->first()?->start_balance;
        $financial_accounts = OrderFinancial::
        selectRaw("financial_id, SUM(amount) as total_amount")
        ->whereIn("order_id", $orders)
        ->with("financials")
        ->groupBy("financial_id")
        ->get()
        ->map(function($item) use($expenses, $financial, $start, $end, $start_balance) {
            $expenses_amount = $expenses
            ->where("financial_account_id", $item->financial_id)
            ->sum("amount") ?? 0;
            $from_financial = $financial
            ->where('from_financial_id', $item->financial_id)
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end)
            ->first()->total;
            $to_financial = $financial
            ->where('to_financial_id', $item->financial_id)
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end)
            ->first()->total;
            $total = $to_financial - $from_financial + $start_balance;
            return [
                "total_amount" => $item->total_amount - $expenses_amount + $total,
                "financial_id" => $item->financial_id,
                "financial_name" => $item?->financials?->name,
            ];
        });

        return response()->json([
            'financial_accounts' => $financial_accounts
        ]);
    }

    public function financial_reports(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'from' => ['date'],
            'to' => ['date'],
            'cashier_id' => ['exists:cashiers,id'],
            'branch_id' => ['exists:branches,id'],
            'cashier_man_id' => ['exists:cashier_men,id'],
            'financial_id' => ['exists:finantiol_acountings,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $start = Carbon::parse('1111-01-01');
        $end = now();
        $shift = $this->cashier_shift 
        ->where('id', $id)
        ->first();
        // Order
        $order_count = Order::
        where('shift', $shift->shift);
        $take_away_orders = Order::
        where('shift', $shift->shift)
        ->where("order_type", "take_away");
        $delivery_orders = Order::
        where('shift', $shift->shift)
        ->where("order_type", "delivery");
        $dine_in_orders = Order::
        where('shift', $shift->shift)
        ->where("order_type", "dine_in");
         
        $expenses = $this->expenses
        ->where('created_at', '>=', $shift->start_time ?? now())
        ->where('created_at', '<=', $shift->end_time ?? now())
        ->with("financial_account");
        
        $expenses_items = $this->expenses
        ->selectRaw("financial_account_id, SUM(amount) AS total")
        ->where('created_at', '>=', $shift->start_time ?? now())
        ->where('created_at', '<=', $shift->end_time ?? now())
        ->with("financial_account")
        ->groupBy("financial_account_id");
        $online_order_paid = $this->orders
        ->selectRaw("payment_method_id, SUM(amount) AS amount")
        ->where("pos", 0)
        ->where('shift', $shift->shift)
        ->where(function($query){
            $query->where("payment_method_id", "!=", 2)
            ->where(function($q){
                $q->where("status", 1)
                ->orWhereNull("status");
            })
            ->orWhereHas("financial_accountigs");
        })
        ->with("payment_method")
        ->groupBy("payment_method_id")
        ->groupBy("order_type");
        $online_order_unpaid = $this->orders
        ->selectRaw("payment_method_id, SUM(amount) AS amount")
        ->where("pos", 0) 
        ->where('shift', $shift->shift)
        ->where("payment_method_id", 2)
        ->where(function($q){
            $q->where("status", 1)
            ->orWhereNull("status");
        }) 
        ->with("payment_method")
        ->groupBy("payment_method_id")
        ->groupBy("order_type");

        if($request->from || $request->to){
            
            $time_sittings = TimeSittings:: 
            get();
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
                $start = Carbon::parse(date('Y-m-d') . ' ' . ' 00:00:00');
                $end = Carbon::parse(date('Y-m-d') . ' ' . ' 23:59:59');
            } 
            $start = Carbon::parse($request->from . ' ' . $start->format('H:i:s'));
            $end = Carbon::parse($request->to . ' ' . $end->format('H:i:s'));
  
            $expenses = $expenses
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
            $order_count = $order_count
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
            $take_away_orders = $take_away_orders
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
            $delivery_orders = $delivery_orders
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end); 
            $dine_in_orders = $dine_in_orders
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
                
            $expenses_items = $expenses_items
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
            $online_order_paid = $online_order_paid
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
            $online_order_unpaid = $online_order_unpaid
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
        }
        if($request->cashier_id){ 

            $expenses = $expenses
            ->where("cashier_id", $request->cashier_id);
            $order_count = $order_count
            ->where("cashier_id", $request->cashier_id);
            $take_away_orders = $take_away_orders
            ->where("cashier_id", $request->cashier_id);
            $delivery_orders = $delivery_orders
            ->where("cashier_id", $request->cashier_id);
            $dine_in_orders = $dine_in_orders
            ->where("cashier_id", $request->cashier_id);

            $expenses_items = $expenses_items
            ->where("cashier_id", $request->cashier_id);
            $online_order_paid = $online_order_paid
            ->where("cashier_id", $request->cashier_id);
            $online_order_unpaid = $online_order_unpaid
            ->where("cashier_id", $request->cashier_id);
        }
        if($request->branch_id){ 
            $expenses = $expenses
            ->where("branch_id", $request->branch_id);
            $order_count = $order_count
            ->where("branch_id", $request->branch_id);
            $take_away_orders = $take_away_orders
            ->where("branch_id", $request->branch_id);
            $delivery_orders = $delivery_orders
            ->where("branch_id", $request->branch_id);
            $dine_in_orders = $dine_in_orders
            ->where("branch_id", $request->branch_id);
            $expenses_items = $expenses_items
            ->where("branch_id", $request->branch_id);
            $online_order_paid = $online_order_paid
            ->where("branch_id", $request->branch_id);
            $online_order_unpaid = $online_order_unpaid
            ->where("branch_id", $request->branch_id);
        }
        if($request->cashier_man_id){
            $expenses = $expenses
            ->where("cashier_man_id", $request->cashier_man_id);
            $order_count = $order_count
            ->where("cashier_man_id", $request->cashier_man_id);
            $take_away_orders = $take_away_orders
            ->where("cashier_man_id", $request->cashier_man_id);
            $delivery_orders = $delivery_orders
            ->where("cashier_man_id", $request->cashier_man_id);
            $dine_in_orders = $dine_in_orders
            ->where("cashier_man_id", $request->cashier_man_id); 
            
            $expenses_items = $expenses_items
            ->where("cashier_man_id", $request->cashier_man_id); 
            $online_order_paid = $online_order_paid
            ->where("cashier_man_id", $request->cashier_man_id); 
            $online_order_unpaid = $online_order_unpaid
            ->where("cashier_man_id", $request->cashier_man_id); 
        }
        if($request->financial_id){
            $expenses = $expenses
            ->where("financial_account_id", $request->financial_id);
            $order_count = $order_count
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });
            $take_away_orders = $take_away_orders
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });
            $delivery_orders = $delivery_orders
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });
            $dine_in_orders = $dine_in_orders
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });  
            
            $expenses_items = $expenses_items
            ->where("financial_account_id", $request->financial_id);
            $online_order_paid = $online_order_paid
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });  
            $online_order_unpaid = $online_order_unpaid
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });  
        }
        // ____________________________________________________________
    
        
        $expenses = $expenses  
        ->get();
        $order_count = $order_count
        ->count();
        $take_away_orders = $take_away_orders
        ->pluck('id')
        ->toArray();
        $delivery_orders = $delivery_orders
        ->pluck('id')
        ->toArray();
        $dine_in_orders = $dine_in_orders
        ->pluck('id')
        ->toArray();

        $delivery_financial_accounts = OrderFinancial::
        selectRaw("financial_id ,SUM(amount) as total_amount")
        ->whereIn("order_id", $delivery_orders)
        ->with("financials")
        ->groupBy("financial_id") 
        ->get();
        $take_away_financial_accounts = OrderFinancial::
        selectRaw("financial_id ,SUM(amount) as total_amount")
        ->whereIn("order_id", $take_away_orders)
        ->with("financials")
        ->groupBy("financial_id") 
        ->get();
        $dine_in_financial_accounts = OrderFinancial::
        selectRaw("financial_id ,SUM(amount) as total_amount")
        ->whereIn("order_id", $dine_in_orders)
        ->with("financials")
        ->groupBy("financial_id") 
        ->get();
        $financial_accounts = [];
        $total_amount = 0;
        foreach ($delivery_financial_accounts as $item) {
            $total_amount += $item->total_amount;
            if(isset($financial_accounts[$item->financial_id])){
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name,
                    "total_amount_delivery" => $item->total_amount + $financial_accounts[$item->financial_id]['total_amount_delivery'], 
                    "total_amount_take_away" => $financial_accounts[$item->financial_id]['total_amount_take_away'],
                    "total_amount_dine_in" => $financial_accounts[$item->financial_id]['total_amount_dine_in'],
                ];
            }
            else{
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name, 
                    "total_amount_delivery" => $item->total_amount ,
                    "total_amount_take_away" => 0,
                    "total_amount_dine_in" => 0,
                ];
            }
        }
        foreach ($take_away_financial_accounts as $item) {
            $total_amount += $item->total_amount;
            if(isset($financial_accounts[$item->financial_id])){
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name, 
                    "total_amount_delivery" => $financial_accounts[$item->financial_id]['total_amount_delivery'], 
                    "total_amount_take_away" => $item->total_amount + $financial_accounts[$item->financial_id]['total_amount_take_away'],
                    "total_amount_dine_in" => $financial_accounts[$item->financial_id]['total_amount_dine_in'],
                ];
            }
            else{
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name, 
                    "total_amount_delivery" => 0 ,
                    "total_amount_take_away" => $item->total_amount,
                    "total_amount_dine_in" => 0,
                ];
            }
        }
        foreach ($dine_in_financial_accounts as $item) {
            $total_amount += $item->total_amount;
            if(isset($financial_accounts[$item->financial_id])){
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name,
                    "total_amount_delivery" => $financial_accounts[$item->financial_id]['total_amount_delivery'], 
                    "total_amount_take_away" => $financial_accounts[$item->financial_id]['total_amount_take_away'],
                    "total_amount_dine_in" => $item->total_amount + $financial_accounts[$item->financial_id]['total_amount_dine_in'],
                ];
            }
            else{
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name,
                    "total_amount_delivery" => 0 ,
                    "total_amount_take_away" => 0,
                    "total_amount_dine_in" => $item->total_amount,
                ];
            }
        }
        $expenses_total = 0;
        foreach ($expenses as $item) {
            $expenses_total += $item->amount;
            $total_amount -= $item->amount;
            if(isset($financial_accounts[$item->financial_account_id])){
                $financial_accounts[$item->financial_account_id] = [
                    "financial_id" => $item->financial_account_id,
                    "financial_name" => $item?->financial_account?->name,
                    "total_amount_delivery" => $financial_accounts[$item->financial_account_id]['total_amount_delivery'] - $item->amount, 
                    "total_amount_take_away" => $financial_accounts[$item->financial_account_id]['total_amount_take_away'],
                    "total_amount_dine_in" => $financial_accounts[$item->financial_account_id]['total_amount_dine_in'],
                ];
            }
            else{
                $financial_accounts[$item->financial_account_id] = [
                    "financial_id" => $item->financial_account_id,
                    "financial_name" => $item?->financial_account?->name,
                    "total_amount_delivery" => -$item->amount ,
                    "total_amount_take_away" => 0,
                    "total_amount_dine_in" => 0,
                ];
            }
        }
        $financial_accounts = collect($financial_accounts);
        $financial_accounts = $financial_accounts->values();
        
        $expenses_items = $expenses_items
        ->get()
        ->map(function($item){
            return [
                "financial_account" => $item?->financial_account?->name,
                "total" => $item->total,
            ];
        });
        $online_order_paid = $online_order_paid
        ->get()
        ->map(function($item){
            return [
                "payment_method" => $item?->payment_method?->name,
                "payment_method_id" => $item->payment_method_id,
                "amount" => $item->amount,
            ];
        });
        $online_order_unpaid = $online_order_unpaid
        ->get()
        ->map(function($item){
            return [
                "payment_method" => $item?->payment_method?->name,
                "payment_method_id" => $item->payment_method_id,
                "amount" => $item->amount,
            ];
        });
        $paid_online_order = [];
        foreach ($online_order_paid as $item) {
            if(isset($paid_online_order[$item['payment_method_id']])){
                $paid_online_order[$item['payment_method_id']] = [
                    "payment_method" => $item['payment_method'],
                    "payment_method_id" => $item['payment_method_id'],
                    "amount" => $item['amount'] + $paid_online_order[$item['payment_method_id']]['amount'],
                ];
            }
            else{
                $paid_online_order[$item['payment_method_id']] = [
                    "payment_method" => $item['payment_method'],
                    "payment_method_id" => $item['payment_method_id'],
                    "amount" => $item['amount'],
                ]; 
            }
        }
        $unpaid_online_order = [];
        foreach ($online_order_unpaid as $item) {
            if(isset($unpaid_online_order[$item['payment_method_id']])){
                $unpaid_online_order[$item['payment_method_id']] = [
                    "payment_method" =>  $item['payment_method'],
                    "payment_method_id" => $item['payment_method_id'],
                    "amount" => $item['amount'] + $unpaid_online_order[$item['payment_method_id']]['amount'],
                ];
            }
            else{
                $unpaid_online_order[$item['payment_method_id']] = [
                    "payment_method" =>  $item['payment_method'],
                    "payment_method_id" => $item['payment_method_id'],
                    "amount" => $item['amount'],
                ]; 
            }
        }
        $online_order = [
            'paid' => array_values($paid_online_order),
            'un_paid' => array_values($unpaid_online_order),
        ];
        $financial_accounts = collect($financial_accounts);
        if($request->financial_id){
            $financial_accounts = $financial_accounts
            ->where("financial_id", $request->financial_id)
            ->values();
        }

        return response()->json([ 
            'financial_accounts' => $financial_accounts,
            'order_count' => $order_count,
            'total_amount' => $total_amount, 
            'expenses_total' => $expenses_total, 
            'expenses' => $expenses_items, 
            'online_order' => $online_order, 
        ]);
    }
}
