<?php

namespace App\Http\Controllers\api\admin\report;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

use App\Models\CashierMan;
use App\Models\Cashier; 
use App\Models\Category; 
use App\Models\OrderFinancial;
use App\Models\FinantiolAcounting;
use App\Models\Branch;
use App\Models\TimeSittings;
use App\Models\Order;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseStock;
use App\Models\Expense;
use App\Models\FinancialHistory;
use App\Models\CashierShift; 
use App\Models\OrderDetail; 
use App\Models\CompanyInfo; 
use App\Models\CafeTable; 
use App\Models\CafeLocation; 
use App\Models\Setting; 

use App\trait\OrderFormat; 

class ReportController extends Controller
{
    public function __construct(private Expense $expenses,
    private Order $orders, private CashierShift $cashier_shift){}
    use OrderFormat; 

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
        $halls = CafeLocation::
        select("id", "name")
        ->get();  
        $tables = CafeTable::
        select("id", "table_number")
        ->get();  

        return response()->json([
            "cashier_man" => $cashier_man,
            "cashier" => $cashier,
            "financial_account" => $financial_account,
            "branches" => $branches,
            "halls" => $halls,
            "tables" => $tables,
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
                $end = $request->to . ' ' . $to->from;
                $hours = $to->hours;
                $minutes = $to->minutes;
                $from = $request->from . ' ' . $from;
                $start = Carbon::parse($from);
                $end = Carbon::parse($end);
                $end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
                if ($start >= $end) {
                    $end = $end->addDay();
                }
                if($start >= now()){
                    $start = $start->subDay();
                    $end = $end->subDay();
                }
    
            } 
            else {
                $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
                $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
            } 
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
        ->get();
        $order_count = $orders->count();
        $orders = $orders->map(function($item, $key) use($order_count){
            return [ 
                'id' => $item->id,
                'order_number' => $order_count - $key,
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
                'delivery_fees' => $item->delivery_fees,
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
            'orders' => $orders, 
            "start" => $start,
            "end" => $end,
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
        select("id")
        ->where("is_void", 0)  
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
         ;
        $expenses = $this->expenses;
        $start = Carbon::parse('1111-01-01');
        $end = now();
        $end = Carbon::parse($end);
        if($request->from || $request->to){
             
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
                $end = $request->to . ' ' . $to->from;
                $hours = $to->hours;
                $minutes = $to->minutes;
                $from = $request->from . ' ' . $from;
                $start = Carbon::parse($from);
                $end = Carbon::parse($end);
                $end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
                if ($start >= $end) {
                    $end = $end->addDay();
                }
                if($start >= now()){
                    $start = $start->subDay(); 
                    $end = $end->subDay();
                }
    
            } else {
                $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
                $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
            }  

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
        $expenses = $expenses->get();
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

    public function cashier_report(Request $request, $id){

        $start = Carbon::parse('1111-01-01');
        $end = now();
        $shift = $this->cashier_shift 
        ->where('id', $id)
        ->first();
        if(empty($shift)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        // Order
        $order_count = Order::
        where('shift', $shift->shift)  
        ->where("is_void", 0)  
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
        ->count();
        $take_away_orders = Order::
        where('shift', $shift->shift)
        ->where("order_type", "take_away") 
        ->where("is_void", 0)  
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
        ->pluck('id')
        ->toArray();
        $delivery_orders = Order::
        where('shift', $shift->shift)
        ->where("order_type", "delivery") 
        ->where("is_void", 0)
        ->where("due_from_delivery", 0)
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
        ->pluck('id')
        ->toArray();
        $out_delivery_orders = Order::
        where('shift', $shift->shift)
        ->where("order_type", "delivery") 
        ->where("is_void", 0)   
        ->where("due_from_delivery", 1)  
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
        ->pluck('id')
        ->toArray();
        $dine_in_orders = Order::
        where('shift', $shift->shift)
        ->where("order_type", "dine_in") 
        ->where("is_void", 0)  
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
        ->pluck('id')
        ->toArray();
         
        $expenses = $this->expenses
        ->where('created_at', '>=', $shift->start_time ?? now())
        ->where('created_at', '<=', $shift->end_time ?? now())
        ->with("financial_account")
        ->get();
        
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
        ->whereDoesntHave("financial_accountigs")
        ->where(function($q){
            $q->where("status", 1)
            ->orWhereNull("status");
        }) 
        ->with("payment_method")
        ->groupBy("payment_method_id")
        ->groupBy("order_type");

        // ____________________________________________________________
    
          

        $delivery_financial_accounts = OrderFinancial::
        selectRaw("financial_id ,SUM(amount) as total_amount")
        ->whereIn("order_id", $delivery_orders)
        ->with("financials")
        ->groupBy("financial_id") 
        ->get();

        $out_delivery_financial_accounts = OrderFinancial::
        selectRaw("financial_id ,SUM(amount) as total_amount")
        ->whereIn("order_id", $out_delivery_orders)
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

    public function financial_reports(Request $request){
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
        // Order
        $order_count = Order::
        select("id") ;
    
        $void_order_count = Order:: 
        whereBetween("created_at", [$start, $end]) 
        ->where("is_void", 1);
        $void_order_sum = Order:: 
        whereBetween("created_at", [$start, $end]) 
        ->where("is_void", 1);
        $take_away_orders = Order::
        select("id") 
        ->where("order_type", "take_away") 
        ->where("is_void", 0)  
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
         ;
        $delivery_orders = Order::
        select("id") 
        ->where("order_type", "delivery") 
        ->where("is_void", 0)  
        ->where("due_from_delivery", 0)
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
         ;
        $out_delivery_orders = Order::
        select("id") 
        ->where("order_type", "delivery") 
        ->where("is_void", 0)  
        ->where("due_from_delivery", 1)
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
         ;
        $dine_in_orders = Order::
        select("id") 
        ->where("order_type", "dine_in") 
        ->where("is_void", 0)  
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
         ;
         
                
        $due_module = Order:: 
        where("due_module", ">", 0);
        $due_user = Order:: 
        where("due", 1);
             
        $expenses = $this->expenses
        ->with("financial_account");
        
        $expenses_items = $this->expenses
        ->selectRaw("financial_account_id, SUM(amount) AS total")
        ->with("financial_account")
        ->groupBy("financial_account_id");
        $online_order_paid = $this->orders
        ->selectRaw("payment_method_id, SUM(amount) AS amount")
        ->where("pos", 0)  
        ->where("is_void", 0)  
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
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
        ->where("is_void", 0)  
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
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
                $end = $request->to ?? date("Y-m-d") . ' ' . $to->from;
                $hours = $to->hours;
                $minutes = $to->minutes;
                $from = ($request->from ?? "199-05-05") . ' ' . $from; 
                $start = Carbon::parse($from);
                $end = Carbon::parse($end);
                $end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes); 
                if ($start >= $end) {
                    $end = $end->addDay();
                }
            } else {
                $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
                $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
            } 
  
            $due_module = $due_module
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
            $due_user = $due_user
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
            $void_order_count = $void_order_count 
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
            $void_order_sum = $void_order_sum 
            ->where("created_at", ">=", $start)
            ->where("created_at", "<=", $end);
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
            $out_delivery_orders = $out_delivery_orders
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

            $void_order_count = $void_order_count 
            ->where("cashier_id", $request->cashier_id);
            $void_order_sum = $void_order_sum 
            ->where("cashier_id", $request->cashier_id);
            $expenses = $expenses
            ->where("cashier_id", $request->cashier_id);
            $order_count = $order_count
            ->where("cashier_id", $request->cashier_id);
            $take_away_orders = $take_away_orders
            ->where("cashier_id", $request->cashier_id);
            $delivery_orders = $delivery_orders
            ->where("cashier_id", $request->cashier_id);
            $out_delivery_orders = $out_delivery_orders
            ->where("cashier_id", $request->cashier_id);
            $dine_in_orders = $dine_in_orders
            ->where("cashier_id", $request->cashier_id);

            $due_module = $due_module
            ->where("cashier_id", $request->cashier_id);
            $due_user = $due_user
            ->where("cashier_id", $request->cashier_id);

            $expenses_items = $expenses_items
            ->where("cashier_id", $request->cashier_id);
            $online_order_paid = $online_order_paid
            ->where("cashier_id", $request->cashier_id);
            $online_order_unpaid = $online_order_unpaid
            ->where("cashier_id", $request->cashier_id);
        }
        if($request->branch_id){ 
            $void_order_count = $void_order_count 
            ->where("branch_id", $request->branch_id);
            $void_order_sum = $void_order_sum 
            ->where("branch_id", $request->branch_id);
            $expenses = $expenses
            ->where("branch_id", $request->branch_id);
            $order_count = $order_count
            ->where("branch_id", $request->branch_id);
            $take_away_orders = $take_away_orders
            ->where("branch_id", $request->branch_id);
            $delivery_orders = $delivery_orders
            ->where("branch_id", $request->branch_id);
            $out_delivery_orders = $out_delivery_orders
            ->where("branch_id", $request->branch_id);
            $dine_in_orders = $dine_in_orders
            ->where("branch_id", $request->branch_id);
            $expenses_items = $expenses_items
            ->where("branch_id", $request->branch_id);
            $online_order_paid = $online_order_paid
            ->where("branch_id", $request->branch_id);
            $online_order_unpaid = $online_order_unpaid
            ->where("branch_id", $request->branch_id);
            $due_module = $due_module
            ->where("branch_id", $request->branch_id);
            $due_user = $due_user
            ->where("branch_id", $request->branch_id);
        }
        if($request->cashier_man_id){
            $void_order_count = $void_order_count 
            ->where("cashier_man_id", $request->cashier_man_id);
            $void_order_sum = $void_order_sum 
            ->where("cashier_man_id", $request->cashier_man_id);
            $expenses = $expenses
            ->where("cahier_man_id", $request->cashier_man_id);
            $order_count = $order_count
            ->where("cashier_man_id", $request->cashier_man_id);
            $take_away_orders = $take_away_orders
            ->where("cashier_man_id", $request->cashier_man_id);
            $delivery_orders = $delivery_orders
            ->where("cashier_man_id", $request->cashier_man_id);
            $out_delivery_orders = $out_delivery_orders
            ->where("cashier_man_id", $request->cashier_man_id);
            $dine_in_orders = $dine_in_orders
            ->where("cashier_man_id", $request->cashier_man_id); 
            $due_module = $due_module
            ->where("cashier_man_id", $request->cashier_man_id); 
            $due_user = $due_user
            ->where("cashier_man_id", $request->cashier_man_id); 
            
            $expenses_items = $expenses_items
            ->where("cahier_man_id", $request->cashier_man_id); 
            $online_order_paid = $online_order_paid
            ->where("cashier_man_id", $request->cashier_man_id); 
            $online_order_unpaid = $online_order_unpaid
            ->where("cashier_man_id", $request->cashier_man_id); 
        }
        if($request->financial_id){
            $void_order_count = $void_order_count 
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });
            $void_order_sum = $void_order_sum 
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });
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
            $out_delivery_orders = $out_delivery_orders
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });
            $dine_in_orders = $dine_in_orders
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });  
            $due_module = $due_module
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });  
            $due_user = $due_user
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
    
    
        $due_module = $due_module
        ->sum("due_module");  
        $due_user = $due_user
        ->sum("amount");  
        $expenses = $expenses  
        ->get();
        $total_tax = $order_count
        ->sum("total_tax");
        $service_fees = $order_count
        ->sum("service_fees"); 
        $order_count = $order_count
        ->count();
        $take_away_orders = $take_away_orders
        ->pluck('id')
        ->toArray();
        $delivery_orders = $delivery_orders
        ->pluck('id')
        ->toArray();
        $out_delivery_orders = $out_delivery_orders 
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
        $out_delivery_financial_accounts = OrderFinancial::
        selectRaw("financial_id ,SUM(amount) as total_amount")
        ->whereIn("order_id", $out_delivery_orders)
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
                    "total_amount_out_delivery" => $financial_accounts[$item->financial_id]['total_amount_out_delivery'],
                ];
            }
            else{
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name, 
                    "total_amount_delivery" => $item->total_amount ,
                    "total_amount_take_away" => 0,
                    "total_amount_dine_in" => 0,
                    "total_amount_out_delivery" => 0,
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
                    "total_amount_out_delivery" => $financial_accounts[$item->financial_id]['total_amount_out_delivery'],
                ];
            }
            else{
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name, 
                    "total_amount_delivery" => 0 ,
                    "total_amount_take_away" => $item->total_amount,
                    "total_amount_dine_in" => 0,
                    "total_amount_out_delivery" => 0,
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
                    "total_amount_out_delivery" => $financial_accounts[$item->financial_id]['total_amount_out_delivery'],
                ];
            }
            else{
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name,
                    "total_amount_delivery" => 0 ,
                    "total_amount_take_away" => 0,
                    "total_amount_dine_in" => $item->total_amount,
                    "total_amount_out_delivery" => 0,
                ];
            }
        }
        foreach ($out_delivery_financial_accounts as $item) {
            $total_amount += $item->total_amount;
            if(isset($financial_accounts[$item->financial_id])){
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name,
                    "total_amount_delivery" => $financial_accounts[$item->financial_id]['total_amount_delivery'], 
                    "total_amount_take_away" => $financial_accounts[$item->financial_id]['total_amount_take_away'],
                    "total_amount_dine_in" => $financial_accounts[$item->financial_id]['total_amount_dine_in'],
                    "total_amount_out_delivery" => $item->total_amount + $financial_accounts[$item->financial_id]['total_amount_out_delivery'],
                ];
            }
            else{
                $financial_accounts[$item->financial_id] = [
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name,
                    "total_amount_delivery" => 0 ,
                    "total_amount_take_away" => 0,
                    "total_amount_dine_in" => 0,
                    "total_amount_out_delivery" => $item->total_amount,
                ];
            }
        }
        // $expenses_total = 0;
        // foreach ($expenses as $item) {
        //     $expenses_total += $item->amount;
        //     $total_amount -= $item->amount;
        //     if(isset($financial_accounts[$item->financial_account_id])){
        //         $financial_accounts[$item->financial_account_id] = [
        //             "financial_id" => $item->financial_account_id,
        //             "financial_name" => $item?->financial_account?->name,
        //             "total_amount_delivery" => $financial_accounts[$item->financial_account_id]['total_amount_delivery'] - $item->amount, 
        //             "total_amount_take_away" => $financial_accounts[$item->financial_account_id]['total_amount_take_away'],
        //             "total_amount_dine_in" => $financial_accounts[$item->financial_account_id]['total_amount_dine_in'],
        //             "total_amount_out_delivery" => $item->total_amount + $financial_accounts[$item->financial_id]['total_amount_out_delivery'],
        //         ];
        //     }
        //     else{
        //         $financial_accounts[$item->financial_account_id] = [
        //             "financial_id" => $item->financial_account_id,
        //             "financial_name" => $item?->financial_account?->name,
        //             "total_amount_delivery" => -$item->amount ,
        //             "total_amount_take_away" => 0,
        //             "total_amount_dine_in" => 0,
        //             "total_amount_out_delivery" => 0,
        //         ];
        //     }
        // }
        $expenses_total = 0;
        foreach ($expenses as $item) {
            $expenses_total += $item->amount;
            $total_amount -= $item->amount;

            $acc_id = $item->financial_account_id;

            if (isset($financial_accounts[$acc_id])) {
                // تحديث الحساب الموجود مسبقاً
                $financial_accounts[$acc_id]['total_amount_delivery'] -= $item->amount;
                // ملاحظة: يمكنك إنقاصها من الإجمالي العام للحساب بدلاً من قسم التوصيل فقط
            } else {
                // إنشاء سجل جديد في حال لم يكن هناك طلبات لهذا الحساب المالي
                $financial_accounts[$acc_id] = [
                    "financial_id" => $acc_id,
                    "financial_name" => $item->financial_account->name ?? 'N/A',
                    "total_amount_delivery" => -$item->amount, 
                    "total_amount_take_away" => 0,
                    "total_amount_dine_in" => 0,
                    "total_amount_out_delivery" => 0,
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

        $void_order_count = $void_order_count 
        ->count();
        $void_order_sum = $void_order_sum 
        ->sum("amount");
 
        return response()->json([ 
            "due_module" => $due_module,
            "due_user" => $due_user,
            "total_tax" => $total_tax,
            "service_fees" => $service_fees,
            'financial_accounts' => $financial_accounts,
            'order_count' => $order_count,
            'total_amount' => $total_amount, 
            'expenses_total' => $expenses_total, 
            'expenses' => $expenses_items, 
            'online_order' => $online_order,
            'void_order_count' => $void_order_count,
            'void_order_sum' => $void_order_sum, 
            'start' => $start->format("Y-m-d H:i"), 
            'end' => $end->format("Y-m-d H:i"), 
        ]);
    }

    public function branches_list(){
        $branches = Branch::
        select("id", "name")
        ->where("status", 1)
        ->get();

        return response()->json([
            "branches" => $branches,
        ]);
    }

    public function instate_order_report(Request $request){
        $validator = Validator::make($request->all(), [
            'branch_id' => ['exists:branches,id'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        } 

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
            $end = date('Y-m-d') . ' ' . $to->from;
            $hours = $to->hours;
            $minutes = $to->minutes;
            $from = date('Y-m-d') . ' ' . $from;
            $start = Carbon::parse($from);
            $end = Carbon::parse($end);
			$end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
			
            if ($start >= $end) {
                $end = $end->addDay();
            }
			if($start >= now()){
                 $start = $start->subDay();
                $end = $end->subDay();
			 }
 
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        } 
        if($request->branch_id){
            $count_orders = Order:: 
            where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end])  
            ->count();
            $avg_orders = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled")
            ->where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end])  
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->avg("amount");
            $total_orders = Order:: 
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled")
            ->where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)   
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->sum("amount");
            $discount = Order::whereNotIn('order_status', ['faild_to_deliver', 'canceled'])
            ->where('branch_id', $request->branch_id)
            ->whereBetween("created_at", [$start, $end])
            ->where("is_void", 0)   
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->selectRaw("
                COALESCE(SUM(total_discount),0) +
                COALESCE(SUM(coupon_discount),0) +
                COALESCE(SUM(free_discount),0) AS total
            ")
            ->value('total');
            $online_web = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)   
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("pos", 0)
            ->where("source", "web")
            ->sum("amount");
            $online_mobile = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)   
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("pos", 0)
            ->where("source", "mobile")
            ->sum("amount");
            $dine_in = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)   
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("order_type", "dine_in") 
            ->sum("amount");
            $delivery = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)   
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("order_type", "delivery") 
            ->where("due_from_delivery", 0)
            ->sum("amount");
            $out_of_delivery = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->where("order_type", "delivery") 
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("due_from_delivery", 1)
            ->sum("amount");
            $take_away = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("order_type", "take_away") 
            ->sum("amount"); 
            $delivery_fees = Order:: 
            where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->sum("delivery_fees"); 
   
            $void_order_count = Order:: 
            whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 1)   
            ->where("branch_id", $request->branch_id) 
            ->count();
            $void_order_sum = Order:: 
            whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 1)    
            ->where("branch_id", $request->branch_id)
            ->sum("amount");
            $total_tax = Order:: 
            where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end])  
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->sum("total_tax");
            $service_fees = Order:: 
            where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end])  
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->sum("service_fees");
            $due_module = Order:: 
            where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
            ->where("due_module", ">", 0)
            ->sum("due_module");
            $paid_module = Order::
            selectRaw("SUM(order_financials.amount) as total_amount, finantiol_acountings.name")
            ->where("orders.branch_id", $request->branch_id)
            ->leftJoin("order_financials", "order_financials.order_id", "orders.id")
            ->leftJoin("finantiol_acountings", "finantiol_acountings.id", "order_financials.financial_id")
            ->whereBetween("orders.created_at", [$start, $end]) 
            ->where("is_void", 0) 
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
            ->whereNotNull("module_id")
            ->groupBy("financial_id",
            "finantiol_acountings.name")
            ->get();
            $order_module = Order:: 
            where("branch_id", $request->branch_id)
            ->whereBetween("orders.created_at", [$start, $end]) 
            ->where("is_void", 0) 
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
            ->whereNotNull("module_id") 
            ->count();
            $due_user = Order:: 
            where("branch_id", $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
            ->where("due", 1)
            ->sum("amount");

            return response()->json([
                "due_module" => $due_module,
                "due_user" => $due_user,
                "total_orders" => $total_orders,
                "service_fees" => $service_fees,
                "avg_orders" => $avg_orders,
                "count_orders" => $count_orders,
                "discount" => $discount,
                "online_web" => $online_web,
                "online_mobile" => $online_mobile,
                "dine_in" => $dine_in,
                "delivery" => $delivery,
                "out_of_delivery" => $out_of_delivery,
                "take_away" => $take_away,
                "void_order_count" => $void_order_count,
                "void_order_sum" => $void_order_sum,
                "total_tax" => $total_tax,
                "delivery_fees" => $delivery_fees,
                "paid_module" => $paid_module,
                "order_module" => $order_module, 
                $start->format("Y-m-d H:i"),
                $end->format("Y-m-d H:i"),
            ]);
        }
        else{ 
            $data = [];
            
            $branches = Branch::get();
            foreach ($branches as $item) {
                $count_orders = Order:: 
                where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end])  
                ->count();
                $avg_orders = Order::
                where("order_status", "!=", "faild_to_deliver")
                ->where("order_status", "!=", "canceled")
                ->where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end])  
                ->where("is_void", 0)  
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->avg("amount");
                $total_orders = Order:: 
                where("order_status", "!=", "faild_to_deliver")
                ->where("order_status", "!=", "canceled")
                ->where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end])  
                ->where("is_void", 0)  
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->sum("amount");
                $discount = Order::
                whereNotIn('order_status', ['faild_to_deliver', 'canceled'])
                ->where('branch_id', $item->id)
                ->whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 0)   
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->selectRaw("
                    COALESCE(SUM(total_discount),0) +
                    COALESCE(SUM(coupon_discount),0) +
                    COALESCE(SUM(free_discount),0) AS total
                ")
                ->value('total');
                $online_web = Order::
                where("order_status", "!=", "faild_to_deliver")
                ->where("order_status", "!=", "canceled") 
                ->where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 0)  
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->where("pos", 0)
                ->where("source", "web")
                ->sum("amount");
                $online_mobile = Order::
                where("order_status", "!=", "faild_to_deliver")
                ->where("order_status", "!=", "canceled") 
                ->where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 0)  
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->where("pos", 0)
                ->where("source", "mobile")
                ->sum("amount");
                $dine_in = Order::
                where("order_status", "!=", "faild_to_deliver")
                ->where("order_status", "!=", "canceled") 
                ->where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 0)  
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->where("order_type", "dine_in") 
                ->sum("amount");
                $delivery = Order::
                where("order_status", "!=", "faild_to_deliver")
                ->where("order_status", "!=", "canceled") 
                ->where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 0)  
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->where("order_type", "delivery") 
                ->where("due_from_delivery", 0)
                ->sum("amount");
                $out_of_delivery = Order::
                where("order_status", "!=", "faild_to_deliver")
                ->where("order_status", "!=", "canceled") 
                ->where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 0)  
                ->where("due_from_delivery", 1) 
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->where("order_type", "delivery") 
                ->sum("amount");
                $take_away = Order::
                where("order_status", "!=", "faild_to_deliver")
                ->where("order_status", "!=", "canceled") 
                ->where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 0)  
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->where("order_type", "take_away") 
                ->sum("amount");
                $void_order_count = Order:: 
                whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 1)   
                ->where("branch_id", $item->id)  
                ->count();
                $void_order_sum = Order:: 
                whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 1)   
                ->where("branch_id", $item->id) 
                ->sum("amount");
                $total_tax = Order:: 
                where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 0)  
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
                ->sum("total_tax"); 
                $delivery_fees = Order:: 
                where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end]) 
                ->where("is_void", 0)  
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->sum("delivery_fees"); 
                $service_fees = Order:: 
                where("branch_id", $item->id)
                ->whereBetween("created_at", [$start, $end])  
                ->where("is_void", 0)  
                ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                ->sum("service_fees");

                $data[] = [ 
                    "service_fees" => $service_fees,
                    "Branch" => $item->name,
                    "total_orders" => $total_orders,
                    "avg_orders" => $avg_orders,
                    "count_orders" => $count_orders,
                    "discount" => $discount,
                    "online_web" => $online_web,
                    "online_mobile" => $online_mobile,
                    "dine_in" => $dine_in,
                    "out_of_delivery" => $out_of_delivery,
                    "delivery" => $delivery,
                    "take_away" => $take_away,
                    "void_order_count" => $void_order_count,
                    "void_order_sum" => $void_order_sum,
                    "total_tax" => $total_tax,
                    "delivery_fees" => $delivery_fees,
                ];
            }
            $count_orders = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->whereBetween("created_at", [$start, $end])
            ->count();
            $avg_orders = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled")
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->avg("amount");
            $total_orders = Order:: 
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled")
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)   
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->sum("amount");
            $discount = Order::whereNotIn('order_status', ['faild_to_deliver', 'canceled'])
            ->where('branch_id', $request->branch_id)
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->selectRaw("
                COALESCE(SUM(total_discount),0) +
                COALESCE(SUM(coupon_discount),0) +
                COALESCE(SUM(free_discount),0) AS total
            ")
            ->value('total');
            $online_web = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("pos", 0)
            ->where("source", "web")
            ->sum("amount");
            $online_mobile = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("pos", 0)
            ->where("source", "mobile")
            ->sum("amount");
            $dine_in = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("order_type", "dine_in") 
            ->sum("amount");
            $delivery = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("order_type", "delivery") 
            ->where("due_from_delivery", 0)
            ->sum("amount");
            $out_of_delivery = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->whereBetween("created_at", [$start, $end]) 
            ->where("due_from_delivery", 1)
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("order_type", "delivery") 
            ->sum("amount");
            $take_away = Order::
            where("order_status", "!=", "faild_to_deliver")
            ->where("order_status", "!=", "canceled") 
            ->whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where("order_type", "take_away") 
            ->sum("amount");
            $void_order_count = Order:: 
            whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 1)  
            ->count();
            $void_order_sum = Order:: 
            whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 1)  
            ->sum("amount");
            $total_tax = Order:: 
            whereBetween("created_at", [$start, $end])  
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->sum("total_tax"); 
            $delivery_fees = Order:: 
            whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->sum("delivery_fees"); 
            $service_fees = Order:: 
            whereBetween("created_at", [$start, $end])  
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->sum("service_fees");
            $due_module = Order:: 
            whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
            ->where("due_module", ">", 0)
            ->sum("due_module");
            $paid_module = Order::
            selectRaw("SUM(order_financials.amount) as total_amount, finantiol_acountings.name")
            ->leftJoin("order_financials", "order_financials.order_id", "orders.id")
            ->leftJoin("finantiol_acountings", "finantiol_acountings.id", "order_financials.financial_id")
            ->whereBetween("orders.created_at", [$start, $end]) 
            ->where("is_void", 0) 
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
            ->whereNotNull("module_id")
            ->groupBy("financial_id",
            "finantiol_acountings.name")
            ->get();
            $order_module = Order:: 
            where("branch_id", $request->branch_id)
            ->whereBetween("orders.created_at", [$start, $end]) 
            ->where("is_void", 0) 
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
            ->whereNotNull("module_id") 
            ->count();
            $due_user = Order:: 
            whereBetween("created_at", [$start, $end]) 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
            ->where("due", 1)
            ->sum("amount");

            return response()->json([
                "due_module" => $due_module,
                "due_user" => $due_user,
                "data" => $data,
                "service_fees" => $service_fees,
                "total_orders" => $total_orders,
                "avg_orders" => $avg_orders,
                "count_orders" => $count_orders,
                "discount" => $discount,
                "online_web" => $online_web,
                "online_mobile" => $online_mobile,
                "dine_in" => $dine_in,
                "delivery" => $delivery,
                "out_of_delivery" => $out_of_delivery,
                "take_away" => $take_away,
                "void_order_count" => $void_order_count,
                "void_order_sum" => $void_order_sum,
                "total_tax" => $total_tax,
                "delivery_fees" => $delivery_fees,
                "paid_module" => $paid_module,
                "order_module" => $order_module,
            ]);
        }
    }

    public function product_report_lists(Request $request){
        $locale = $request->locale ?? "ar";
        $products = Product:: 
        with("translations")
        ->get()
        ->map(function($element) use($locale){
            $name = $element
            ->translations
            ->where("locale", $locale)
            ->where("key", $element->name)
            ->first()?->value ?? $element->name;

            return [
                "id" => $element->id,
                "name" => $name, 
                'category_id' => $element->category_id,
                'sub_category_id' => $element->sub_category_id,
            ];
        });
        
        $categories = Category:: 
        with("translations")
        ->get()
        ->map(function($element) use($locale){
            $name = $element
            ->translations
            ->where("locale", $locale)
            ->where("key", $element->name)
            ->first()?->value ?? $element->name;

            return [
                "id" => $element->id,
                "name" => $name,
            ];
        });
        
        $branches = Branch:: 
        with("translations")
        ->get()
        ->map(function($element) use($locale){
            $name = $element
            ->translations
            ->where("locale", $locale)
            ->where("key", $element->name)
            ->first()?->value ?? $element->name;

            return [
                "id" => $element->id,
                "name" => $name,
            ];
        });
        $cashiers = Cashier::
        with("translations")
        ->get()
        ->map(function($element) use($locale){
            $name = $element
            ->translations
            ->where("locale", $locale)
            ->where("key", $element->name)
            ->first()?->value ?? $element->name;

            return [
                "id" => $element->id,
                "name" => $name,
            ];
        });
        $cashier_men = CashierMan:: 
        get()
        ->map(function($element){ 

            return [
                "id" => $element->id,
                "user_name" => $element->user_name,
            ];
        });

        return response()->json([
            "products" => $products,
            "categories" => $categories,
            "branches" => $branches,
            "cashiers" => $cashiers,
            "cashier_men" => $cashier_men,
        ]);
    }

    public function product_report(Request $request){
        $validator = Validator::make($request->all(), [
            'branch_id' => ['exists:branches,id'],
            'cashier_id' => ['exists:cashiers,id'],
            'category_id' => ['exists:categories,id'],
            'cashier_man_id' => ['exists:cashier_men,id'],
            'from' => ['date'], 
            'to' => ['date'],
            'sort' => ['in:desc,asc'],
            'products' => ['array'],
            'products.*' => ['exists:products,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $locale = $request->locale ?? "ar";
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
            $end = $request->to . ' ' . $to->from;
            $hours = $to->hours;
            $minutes = $to->minutes;
            $from = $request->from . ' ' . $from;
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
        $orders = Order::
        where("is_void", 0)  
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
        // ->whereBetween("created_at", [$start, $end]) 
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_active', 1);
        if($request->from || $request->to){
            $orders = $orders 
            ->whereBetween("created_at", [$start, $end]);
        }
        if($request->branch_id){
            $orders = $orders->where("branch_id", $request->branch_id);
        }
        if($request->cashier_id){
            $orders = $orders->where("cashier_id", $request->cashier_id);
        }
        if($request->cashier_man_id){
            $orders = $orders->where("cashier_man_id", $request->cashier_man_id);
        }
        $orders = $orders->get();
        $products = [];
        foreach ($orders as $item) {
            $details = $item->order_details_data;
            if(!empty($details)){
                foreach ($details as $element) {
                    $price = 0;
                    foreach ($element['variations'] as $key => $value) {
                        foreach ($value['options'] as $key => $option) {
                            $price += $option['price_after_tax'] 
                            - $option['price']
                            + $option['after_disount'];
                        }
                    }
                    foreach ($element['extras'] as $key => $extra) {
                            $price += $extra['price_after_tax'] 
                            - $extra['price']
                            + $extra['price_after_discount'];
                    }
                    $price += $element['product'][0]['product']['price_after_tax'] 
                        - $element['product'][0]['product']['price']
                        + $element['product'][0]['product']['price_after_discount'];
                    $count = $element['product'][0]['count'];
                    $product_id = $element['product'][0]['product']['id'];
                
                    if(isset($products[$product_id])){
                        $products[$product_id]["price"] += $price * $count;
                        $products[$product_id]["count"] += $count;
                    }
                    else{ 
                        $category_id = $element['product'][0]['product']['category_id'];
                        $sub_category_id = $element['product'][0]['product']['sub_category_id'];
                        // $category = Category::
                        // where("id", $category_id)
                        // ->first()?->name;
                        // $sub_category = Category::
                        // where("id", $sub_category_id)
                        // ->first()?->name;
                        
                        $product_name_item = $element['product'][0]['product']['name'];
                        if($locale != "en"){ 
                            $product_name = Product::
                            where("id", $product_id)
                            ->with("translations")
                            ->first();
                            $product_name = $product_name
                            ->translations
                            ->where("locale", $locale)
                            ->where("key", $product_name_item)
                            ->first()?->value ?? $product_name_item;
                        }
                        else{
                            $product_name = $product_name_item;
                        }
                        $products[$product_id] = [
                            "id" => $product_id,
                            "name" => $product_name,
                            "category_id" => $category_id,
                            "sub_category_id" => $sub_category_id, 
                            "price" => $price * $count,
                            "count" => $count, 
                        ];
                    }
                }
            }
        }
        $categories = Category::
        with("translations");
        if($request->category_id){
            $categories = $categories
            ->where("id", $request->category_id);
        }
        $categories = $categories->get()
        ->map(function($element) use($locale){
            $name = $element
            ->translations
            ->where("locale", $locale)
            ->where("key", $element->name)
            ->first()?->value ?? $element->name;

            return [
                "id" => $element->id,
                "name" => $name,
            ];
        });
        $products = collect($products);
        $data = [];
        foreach ($categories as $key => $item) {
            if(!$request->sort || $request->sort == "desc"){
                $products_item = $products
                ->sortByDesc("price");
            }
            else{ 
                $products_item = $products
                ->sortBy("price");
            }
            if($request->products){
                $products_item = $products_item->filter(function ($product) use ($item, $request) {
                    return ($product['category_id'] == data_get($item, 'id')
                    || $product['sub_category_id'] ==  data_get($item, 'id'))
                    && in_array($product['id'], $request->products);
                });
                if(count($products_item) == 0){
                    continue;
                }
            }
            else{
                $products_item = $products_item->filter(function ($product) use ($item, $request) {
                    return $product['category_id'] ==  data_get($item, 'id')
                        || $product['sub_category_id'] ==  data_get($item, 'id');
                });
            }
             $products_item = $products_item->values();
            $data[] = [
                "id" =>  data_get($item, 'id'),
                "category" => data_get($item, 'name'),
                "products" => $products_item,
                "products_count" => $products_item->sum("count"),
                "products_price" => $products_item->sum("price"),
            ];
        }

        return response()->json([
            "data" => $data,
        ]);
    }

    public function invoices_filter(Request $request){
        $validator = Validator::make($request->all(), [
            'branch_id' => ['exists:branches,id'],
            'cashier_id' => ['exists:cashiers,id'],
            'from' => ['date'], 
            'to' => ['date'],
            'financial_id' => ['exists:cashiers,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $orders = Order::
        with(['user', 'address.zone.city', 'admin:id,name,email,phone,image', 
        'branch', 'delivery', "financial_accountigs"]);
        if($request->branch_id){
            $orders = $orders
            ->where("branch_id", $request->branch_id);
        }
        if($request->cashier_id){
            $orders = $orders
            ->where("cashier_id", $request->cashier_id);
        }
        if($request->financial_id){
            $orders = $orders
            ->whereHas("financial_accountigs", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            }); 
        }
        
        if($request->from || $request->to){
            
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
                $end = $request->to ?? date("Y-m-d") . ' ' . $to->from;
                $hours = $to->hours;
                $minutes = $to->minutes;
                $from = ($request->from ?? "1999-05-05") . ' ' . $from;
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
            $orders = $orders 
            ->whereBetween("created_at", [$start, $end]);
        }
        $orders = $orders->get();
        $locale = $request->locale ?? "en";
        $invoices = $this->invoice_format($orders, $locale);

        $logo_link = CompanyInfo::
        first()?->logo_link;

        return response()->json([
            'invoices' => $invoices,
            'logo_link' => $logo_link,
        ]); 
    }

    public function dine_in_report(Request $request){
        $validator = Validator::make($request->all(), [
            'branch_id' => ['exists:branches,id'],
            'from' => ['date'], 
            'to' => ['date'],
            'table_id' => ['exists:cafe_tables,id'],
            'hall_id' => ['exists:cafe_locations,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $orders = Order::
        where("order_type", "dine_in")
        ->where("is_void", 0) ;
        
        if($request->from || $request->to){
            
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
                $end = $request->to ?? date("Y-m-d") . ' ' . $to->from;
                $hours = $to->hours;
                $minutes = $to->minutes;
                $from = ($request->from ?? "1999-05-05") . ' ' . $from;
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
            $orders = $orders 
            ->whereBetween("orders.created_at", [$start, $end]);
        }
        if($request->branch_id){
            $orders = $orders->where("orders.branch_id", $request->branch_id);
        }
        if($request->table_id){
            $orders = $orders->where("orders.table_id", $request->table_id);
        }
        if($request->hall_id){
            $orders = $orders->whereHas("table", function($query) use($request){
                $query->where("location_id", $request->hall_id);
            });
        }
        $captain_orders = $orders
        ->selectRaw("count(*) AS order_count, sum(amount) AS sum_order, captain_id")
        ->with("captain:id,name")
        ->groupBy("captain_id")
        ->get();
        $table_orders = $orders
        ->selectRaw("count(*) AS order_count, sum(amount) AS sum_order, table_id")
        ->with("table:id,table_number")
        ->groupBy("table_id")
        ->get();
        $hall_orders = $orders
        ->leftJoin('cafe_tables', 'orders.table_id', '=', 'cafe_tables.id')
        ->leftJoin('cafe_locations', 'cafe_tables.location_id', '=', 'cafe_locations.id')
        ->selectRaw('
            COUNT(orders.id) AS order_count,
            SUM(orders.amount) AS sum_order,
            cafe_locations.id AS location_id,
            cafe_locations.name AS location_name
        ')
        ->groupBy('cafe_locations.id', 'cafe_locations.name')
        ->get();

        return response()->json([
            "captain_orders" => $captain_orders,
            "table_orders" => $table_orders,
            "hall_orders" => $hall_orders,
        ]);
    }

    public function group_module_report(Request $request){ 
        $validator = Validator::make($request->all(), [
            'branch_id' => ['exists:branches,id'],
            'from' => ['date'], 
            'to' => ['date'],
            'financial_id' => ['exists:finantiol_acountings,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $paid_module = Order::
        selectRaw("SUM(order_financials.amount) as total_amount, finantiol_acountings.name, financial_id")
        ->leftJoin("order_financials", "order_financials.order_id", "orders.id")
        ->leftJoin("finantiol_acountings", "finantiol_acountings.id", "order_financials.financial_id")
        ->where("is_void", 0) 
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
        ->whereNotNull("module_id")
        ->groupBy("financial_id",
        "finantiol_acountings.name");
        $unpaid_module = Order::
        where("is_void", 0) 
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
        ->whereNotNull("module_id") ;
        $count_module = Order:: 
        where("is_void", 0) 
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"]) 
        ->whereNotNull("module_id");
        
        if($request->from || $request->to){
            
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
                $end = $request->to ?? date("Y-m-d") . ' ' . $to->from;
                $hours = $to->hours;
                $minutes = $to->minutes;
                $from = ($request->from ?? "1999-05-05") . ' ' . $from;
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
            $paid_module = $paid_module 
            ->whereBetween("orders.created_at", [$start, $end]);
            $unpaid_module = $unpaid_module 
            ->whereBetween("orders.created_at", [$start, $end]);
            $count_module = $count_module 
            ->whereBetween("orders.created_at", [$start, $end]);
        }
        if($request->branch_id){
            $paid_module = $paid_module 
            ->where("orders.branch_id", $request->branch_id);
            $unpaid_module = $unpaid_module 
            ->where("orders.branch_id", $request->branch_id);
            $count_module = $count_module 
            ->where("orders.branch_id", $request->branch_id);
        }
        if($request->financial_id){
            $paid_module = $paid_module 
            ->where("financial_id", $request->financial_id);
            $unpaid_module = $unpaid_module  
            ->whereHas("financials", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });
            $count_module = $count_module  
            ->whereHas("financials", function($query) use($request){
                $query->where("finantiol_acountings.id", $request->financial_id);
            });
        }
        
        $paid_module = $paid_module
        ->get();
        $unpaid_module = $unpaid_module
        ->sum("due_module");
        $count_module = $count_module
        ->count();

        return response()->json([
            "paid_module" => $paid_module,
            "unpaid_module" => $unpaid_module,
            "count_module" => $count_module,
        ]);
    }

    public function hall_reports(Request $request){ 
        $validator = Validator::make($request->all(), [
            'branch_id' => ['exists:branches,id'],
            'from' => ['date'], 
            'to' => ['date'],
            'cashier_man_id' => ['exists:cashier_men,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $time_sittings = TimeSittings::
        get();
        $rows = CafeLocation::query()
        ->selectRaw("
            cafe_locations.id as hall_id,
            cafe_locations.name as hall_name,
            finantiol_acountings.id as account_id,
            finantiol_acountings.name as account_name,
            SUM(order_financials.amount) as amount
        ")
        ->leftJoin('cafe_tables', 'cafe_tables.location_id', '=', 'cafe_locations.id')
        ->leftJoin('orders', function ($join) use ($request, $time_sittings) {
            
            $join->on('orders.table_id', '=', 'cafe_tables.id')
            ->where('orders.is_void', 0);
            if($request->from || $request->to){
                

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
                    $end = $request->to ?? date("Y-m-d") . ' ' . $to->from;
                    $hours = $to->hours;
                    $minutes = $to->minutes;
                    $from = ($request->from ?? "1999-05-05") . ' ' . $from;
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
                $join
                ->whereBetween("orders.created_at", [$start, $end]);
            }
            if($request->cashier_man_id){
                $join
                ->where("cashier_man_id", $request->cashier_man_id);
            }
            if($request->branch_id){
                $join
                ->where("branch_id", $request->branch_id);
            } 
        })
        ->leftJoin('order_financials', 'order_financials.order_id', '=', 'orders.id')
        ->leftJoin(
            'finantiol_acountings',
            'finantiol_acountings.id',
            '=',
            'order_financials.financial_id'
        )
        ->groupBy(
            'cafe_locations.id',
            'cafe_locations.name',
            'finantiol_acountings.id',
            'finantiol_acountings.name'
        );

        $rows = $rows->get();
        $hall_orders = $rows
            ->groupBy('hall_id')
            ->map(function ($items) {
                return [
                    'hall_id'   => $items->first()->hall_id,
                    'hall_name' => $items->first()->hall_name,
                    'accounts'  => $items
                        ->whereNotNull('account_id')
                        ->map(function ($row) {
                            return [
                                'account_id'   => $row->account_id,
                                'account_name' => $row->account_name,
                                'amount'       => $row->amount,
                            ];
                        })
                        ->values(),
                ];
            })
            ->values();

        return response()->json([
            "halls" => $hall_orders
        ]);
    }
}
