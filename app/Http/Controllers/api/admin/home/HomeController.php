<?php

namespace App\Http\Controllers\api\admin\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\SmsIntegration; 
use App\Models\SmsBalance; 

use App\Models\Order;
use App\Models\Product;
use App\Models\OrderDetail;
use App\Models\OrderFinancial;
use App\Models\CafeTable;
use App\Models\Deal;
use App\Models\User;
use App\Models\Setting;
use App\Models\TimeSittings;
use App\Models\LogOrder;
use App\Models\Expense;
use App\Models\CashierMan;
use App\Models\OrderCart;

class HomeController extends Controller
{
    public function __construct(private Order $orders, private Product $products,
    private Deal $deals, private User $users, private Setting $settings, 
    private SmsIntegration $sms_integration, private SmsBalance $sms_balance,
    private TimeSittings $TimeSittings, private LogOrder $log_order){}

    public function home_data(Request $request){
        $validator = Validator::make($request->all(), [
            'from' => 'date',
            'to' => 'date',
            'branch_id' => 'exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $from_date = $request->from ?? date("Y-m-d");
        $to_date = $request->to ?? date("Y-m-d");
        $time_sittings = $this->TimeSittings 
        ->get();
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
            $end = $to_date . ' ' . $to->from;
            $hours = $to->hours;
            $minutes = $to->minutes;
            $from = $from_date . ' ' . $from;
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
            $start = Carbon::parse($from_date . ' 00:00:00');
            $end = Carbon::parse($to_date . ' 23:59:59');
        } 
        $branch_id = $request->branch_id;
        $from = $request->from ? $from : null;
        $to = $request->to ? $to : null;

        $active_orders = CafeTable::
        whereIn("current_status", ["not_available_with_order"]);
        if($branch_id){
            $active_orders->where("branch_id", $branch_id);
        }
        $active_orders = $active_orders->count();
        $occupied_tables = CafeTable::
        whereIn("current_status", ["not_available_pre_order", "not_available_with_order", "not_available_but_checkout"]);
        if($branch_id){
            $occupied_tables->where("branch_id", $branch_id);
        }
        $occupied_tables = $occupied_tables->count();
        $tables_ids = CafeTable::
        whereIn("current_status", ["not_available_pre_order", "not_available_with_order"]);
        if($branch_id){
            $tables_ids->where("branch_id", $branch_id);
        }
        $tables_ids = $tables_ids->pluck("id")
        ->toArray();
        $active_amount = OrderCart::
        whereIn("table_id", $tables_ids);
        if($branch_id){
            $active_amount->where("branch_id", $branch_id);
        }
        $active_amount = $active_amount->sum("amount");

        $online_cashiers = CashierMan::whereHas("cashier", function($query) use($branch_id){
                if($branch_id){
                    $query->where("branch_id", $branch_id);
                }
            })
            ->whereHas("tokens", function($query) {
                // بنشيك هنا على حقل last_used_at في جدول الـ tokens
                $query->where('last_used_at', '>=', now()->subMinutes(5));
            })
            ->count();
        $top_product = OrderDetail::
        selectRaw("product_id, sum(count) as total_sales")
        ->whereNull("exclude_id")
        ->whereNull("addon_id")
        ->whereNull("offer_id")
        ->whereNull("extra_id")
        ->whereNull("variation_id")
        ->whereNull("option_id")
        ->whereNull("deal_id")
        ->groupBy("product_id")
        ->with("product:id,name")
        ->orderByDesc("total_sales")
        ->limit(5);
        $top_financial = OrderFinancial::
        selectRaw("financial_id, sum(amount) as total_amount")
        ->groupBy("financial_id")
        ->with("financials:id,name") 
        ->limit(5);
        $top_payment_method = Order::
        selectRaw("payment_method_id, sum(amount) as total_amount")
        ->whereNotNull("payment_method_id")
        ->groupBy("payment_method_id")
        ->with("payment_method:id,name")
        ->where(function($query){
            $query->where("order_status", "!=", "returned")
            ->where("order_status", "!=", "refund")
            ->where("is_void", 0);
        })
        ->limit(5);
        $order_types = Order::
        selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, count(id) as order_count, order_type")
        ->groupBy("hour")
        ->groupBy("order_type") 
        ->where("created_at", ">=", $start)
        ->where("created_at", "<=", $end) 
        ->where(function($query){
            $query->where("order_status", "!=", "returned")
            ->where("order_status", "!=", "refund")
            ->where("is_void", 0);
        });
        $sales_hourly = Order::
        selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, sum(amount) as total_amount")
        ->groupBy("hour") 
        ->where("created_at", ">=", $start)
        ->where("created_at", "<=", $end) 
        ->where(function($query){
            $query->where("order_status", "!=", "returned")
            ->where("order_status", "!=", "refund")
            ->where("is_void", 0);
        });
        $return_hourly = Order::
        selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, sum(amount) as total_amount")
        ->groupBy("hour") 
        ->where("created_at", ">=", $start)
        ->where("created_at", "<=", $end)
        ->where(function($query){
            $query->where("order_status", "returned")
            ->orWhere("order_status", "refund")
            ->orWhere("is_void", 1);
        });
        $expenses_hourly = Expense::
        selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, sum(amount) as total_amount")
        ->groupBy("hour") 
        ->where("created_at", ">=", $start)
        ->where("created_at", "<=", $end) ;
        $discount_hourly = Order::
        selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, sum(total_discount) as total_discount")
        ->groupBy("hour") 
        ->where("created_at", ">=", $start)
        ->where("created_at", "<=", $end)
        ->where(function($query){
            $query->where("order_status", "!=", "returned")
            ->where("order_status", "!=", "refund")
            ->where("is_void", 0);
        });
        $branch_sales = Order::
        selectRaw("branch_id, sum(amount) as total_amount")
        ->groupBy("branch_id")  
        ->where(function($query){
            $query->where("order_status", "!=", "returned")
            ->where("order_status", "!=", "refund")
            ->where("is_void", 0);
        })
        ->orderByDesc("total_amount")
        ->with("branch:id,name");

        if($branch_id){ 
            $top_product = $top_product
            ->whereHas("order", function($query) use($branch_id){
                $query->where("branch_id", $branch_id);
            });
            $top_financial
            ->whereHas("order", function($query) use($branch_id){
                $query->where("branch_id", $branch_id);
            });
            $top_payment_method
            ->where("branch_id", $branch_id);
            $order_types
            ->where("branch_id", $branch_id);
            $sales_hourly
            ->where("branch_id", $branch_id);
            $return_hourly
            ->where("branch_id", $branch_id);
            $discount_hourly
            ->where("branch_id", $branch_id);
            $branch_sales
            ->where("branch_id", $branch_id);
            $expenses_hourly
            ->where("branch_id", $branch_id);
        }
        if($from){
            $top_product
            ->where("created_at", "<=", $from);
            $top_financial
            ->where("created_at", "<=", $from);
            $top_payment_method
            ->where("created_at", "<=", $from);
            $order_types
            ->where("created_at", "<=", $from);
            $sales_hourly
            ->where("created_at", "<=", $from);
            $return_hourly
            ->where("created_at", "<=", $from);
            $discount_hourly
            ->where("created_at", "<=", $from);
            $branch_sales
            ->where("created_at", "<=", $from);
            $expenses_hourly
            ->where("created_at", "<=", $from);
        }
        if($to){
            $top_product
            ->where("created_at", ">=", $from);
            $top_financial
            ->where("created_at", ">=", $from);
            $top_payment_method
            ->where("created_at", ">=", $from);
            $order_types
            ->where("created_at", ">=", $from);
            $sales_hourly
            ->where("created_at", ">=", $from);
            $return_hourly
            ->where("created_at", ">=", $from);
            $discount_hourly
            ->where("created_at", ">=", $from);
            $branch_sales
            ->where("created_at", ">=", $from);
            $expenses_hourly
            ->where("created_at", ">=", $from);
        }

        $top_product = $top_product->get();
        $top_financial = $top_financial->get();
        $top_payment_method = $top_payment_method->get();
        $order_types = $order_types->get();
        $sales_hourly = $sales_hourly->get();
        $return_hourly = $return_hourly->get();
        $discount_hourly = $discount_hourly->get();
        $branch_sales = $branch_sales->get();
        $expenses_hourly = $expenses_hourly->get();
 
        return response()->json([
            "online_cashiers" => $online_cashiers,
            "active_amount" => $active_amount,
            "occupied_tables" => $occupied_tables,
            "active_orders" => $active_orders,
            "top_product" => $top_product,
            "top_financial" => $top_financial,
            "top_payment_method" => $top_payment_method,
            "order_types" => $order_types,
            "sales_hourly" => $sales_hourly,
            "return_hourly" => $return_hourly,
            "discount_hourly" => $discount_hourly,
            "branch_sales" => $branch_sales,
            "expenses_hourly" => $expenses_hourly,
        ]);
    }

    public function home_orders_count(){ 
        
        $response = Http::get('https://clientbcknd.food2go.online/admin/v1/my_sms_package')->body();
        $response = json_decode($response);

        $sms_subscription_data = collect($response?->user_sms) ?? collect([]); 
        $sms_subscription = $sms_subscription_data->where('back_link', url(''))
        ->where('from', '<=', date('Y-m-d'))->where('to', '>=', date('Y-m-d'))
        ->first();
        $msg_number = $this->sms_balance
        ->where('package_id', $sms_subscription?->id)
        ->first();
        $msg_package = [];
        if (!empty($sms_subscription) && empty($msg_number)) {
            $msg_number = $this->sms_balance
            ->create([
                'package_id' => $sms_subscription->id,
                'balance' => $sms_subscription->msg_number,
            ]);
        }
        $sms_subscription = $sms_subscription_data->where('back_link', url(''))
        ->where('from', '<=', date('Y-m-d'))->where('to', '>=', date('Y-m-d'))
			->sortByDesc('id')
        ->values();
        $msg_number = $this->sms_balance
        ->whereIn('package_id', $sms_subscription?->pluck('id') ?? collect([]))
        ->sum('balance');
        $msg_package['msg_number'] = $msg_number;
        $msg_package['from'] = count($sms_subscription) > 0 ? $sms_subscription[0]?->from : null;
        $msg_package['to'] = count($sms_subscription) > 0 ? $sms_subscription[0]?->to : null;
        $msg_package = env('APP_NAME') == 'Lamada' ? false: $msg_package;
        $orders = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->count(); 
        $pending = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'pending') 
        ->count();
        $confirmed = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'confirmed') 
        ->count();
        $processing = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'processing') 
        ->count();
        $out_for_delivery = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })  
        ->where('order_status', 'out_for_delivery') 
        ->count();
        $delivered = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })  
        ->where('order_status', 'delivered') 
        ->count();
        $returned = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'returned') 
        ->count();
        $faild_to_deliver = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'faild_to_deliver') 
        ->count();
        $canceled = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'canceled') 
        ->count();
        $scheduled = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'scheduled') 
        ->count();
        $orders_count = [
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
            'msg_package' => $msg_package
        ];

        return response()->json($orders_count);
    }

    public function home(Request $request){
        // https://bcknd.food2go.online/admin/home
        
        $validator = Validator::make($request->all(), [
            'from' => 'date',
            'to' => 'date',
            'branch_id' => 'exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $from_date = $request->from ?? date("Y-m-d");
        $to_date = $request->to ?? date("Y-m-d");
        $time_sittings = $this->TimeSittings 
        ->get();
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
            $end = $to_date . ' ' . $to->from;
            $hours = $to->hours;
            $minutes = $to->minutes;
            $from = $from_date . ' ' . $from;
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
            $start = Carbon::parse($from_date . ' 00:00:00');
            $end = Carbon::parse($to_date . ' 23:59:59');
        } 
        $branch_id = $request->branch_id;
        $from = $request->from ? $from : null;
        $to = $request->to ? $to : null;
        
        $response = Http::get('https://clientbcknd.food2go.online/admin/v1/my_sms_package')->body();
        $response = json_decode($response);

        $sms_subscription_data = collect($response?->user_sms) ?? collect([]); 
        $sms_subscription = $sms_subscription_data->where('back_link', url(''))
        ->where('from', '<=', date('Y-m-d'))->where('to', '>=', date('Y-m-d'))
        ->first();
        $msg_number = $this->sms_balance
        ->where('package_id', $sms_subscription?->id)
        ->first();
        $msg_package = [];
        if (!empty($sms_subscription) && empty($msg_number)) {
            $msg_number = $this->sms_balance
            ->create([
                'package_id' => $sms_subscription->id,
                'balance' => $sms_subscription->msg_number,
            ]);
        }
        $sms_subscription = $sms_subscription_data->where('back_link', url(''))
        ->where('from', '<=', date('Y-m-d'))->where('to', '>=', date('Y-m-d'))
			->sortByDesc('id')
        ->values();
        $msg_number = $this->sms_balance
        ->whereIn('package_id', $sms_subscription?->pluck('id') ?? collect([]))
        ->sum('balance');
        $msg_package['msg_number'] = $msg_number;
        $msg_package['from'] = count($sms_subscription) > 0 ? $sms_subscription[0]?->from : null;
        $msg_package['to'] = count($sms_subscription) > 0 ? $sms_subscription[0]?->to : null;
 
        $this->log_order
        ->whereDate('created_at', '<=', now()->subDays(14))
        ->delete();

        $currentYear = Carbon::now()->year; 
        $all_orders = $this->orders
        ->select('created_at', 'amount')
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc('id');
        if($branch_id){
            $all_orders
            ->where("branch_id", $branch_id);
        }
        if($from){
            $all_orders
            ->where("created_at", "<=", $from);
        }
        if($to){
            $all_orders
            ->where("created_at", ">=", $to);
        }
        $all_orders = $all_orders->get();

        $orders_jan = $all_orders
        ->where('order_date', '>=', $currentYear . '-01-01')
        ->where('order_date', '<', $currentYear . '-02-01');
        $orders_feb = $all_orders
        ->where('order_date', '>=', $currentYear . '-02-01')
        ->where('order_date', '<', $currentYear . '-03-01');
        $orders_mar = $all_orders
        ->where('order_date', '>=', $currentYear . '-03-01')
        ->where('order_date', '<', $currentYear . '-04-01');
        $orders_apr = $all_orders
        ->where('order_date', '>=', $currentYear . '-04-01')
        ->where('order_date', '<', $currentYear . '-05-01');
        $orders_may = $all_orders
        ->where('order_date', '>=', $currentYear . '-05-01')
        ->where('order_date', '<', $currentYear . '-06-01');
        $orders_jun = $all_orders
        ->where('order_date', '>=', $currentYear . '-06-01')
        ->where('order_date', '<', $currentYear . '-07-01');
        $orders_jul = $all_orders
        ->where('order_date', '>=', $currentYear . '-07-01')
        ->where('order_date', '<', $currentYear . '-08-01');
        $orders_aug = $all_orders
        ->where('order_date', '>=', $currentYear . '-08-01')
        ->where('order_date', '<', $currentYear . '-09-01');
        $orders_sep = $all_orders
        ->where('order_date', '>=', $currentYear . '-09-01')
        ->where('order_date', '<', $currentYear . '-10-01');
        $orders_oct = $all_orders
        ->where('order_date', '>=', $currentYear . '-10-01')
        ->where('order_date', '<', $currentYear . '-11-01');
        $orders_nov = $all_orders
        ->where('order_date', '>=', $currentYear . '-11-01')
        ->where('order_date', '<', $currentYear . '-12-01');
        $orders_dec = $all_orders
        ->where('order_date', '>=', $currentYear . '-12-01')
        ->where('order_date', '<', ($currentYear + 1) . '-01-01');
        $order_statistics = [
            'Jan' => $orders_jan->count(),
            'Feb' => $orders_feb->count(),
            'Mar' => $orders_mar->count(),
            'Apr' => $orders_apr->count(),
            'May' => $orders_may->count(),
            'Jun' => $orders_jun->count(),
            'Jul' => $orders_jul->count(),
            'Aug' => $orders_aug->count(),
            'Sep' => $orders_sep->count(),
            'Oct' => $orders_oct->count(),
            'Nov' => $orders_nov->count(),
            'Dec' => $orders_dec->count(),
        ];
        $earning_statistics = [
            'Jan' => $orders_jan->sum('amount'),
            'Feb' => $orders_feb->sum('amount'),
            'Mar' => $orders_mar->sum('amount'),
            'Apr' => $orders_apr->sum('amount'),
            'May' => $orders_may->sum('amount'),
            'Jun' => $orders_jun->sum('amount'),
            'Jul' => $orders_jul->sum('amount'),
            'Aug' => $orders_aug->sum('amount'),
            'Sep' => $orders_sep->sum('amount'),
            'Oct' => $orders_oct->sum('amount'),
            'Nov' => $orders_nov->sum('amount'),
            'Dec' => $orders_dec->sum('amount'),
        ];
        $top_selling = $this->products
        ->withCount(['order' => function($query) use ($branch_id, $from, $to) {
            if ($branch_id) {
                $query->where("branch_id", $branch_id);
            }
            if ($from) {
                $query->where("created_at", ">=", $from); 
            }
            if ($to) {
                $query->where("created_at", "<=", $to);
            }
        }]) 
        ->get();
        $top_selling = $top_selling->sortByDesc("order_count")->values();
       
        $today = Carbon::now()->format('l');
        $deals = $this->deals
        ->with('times')
        ->where('daily', 1)
        ->where('status', 1)
        ->where('start_date', '<=', date('Y-m-d'))
        ->where('end_date', '>=', date('Y-m-d'))
        ->orWhere('status', 1)
        ->where('start_date', '<=', date('Y-m-d'))
        ->where('end_date', '>=', date('Y-m-d'))
        ->whereHas('times', function($query) use($today) {
            $query->where('day', $today)
            ->where('from', '<=', now()->format('H:i:s'))
            ->where('to', '>=', now()->format('H:i:s'));
        })
        ->get();
        $users = $this->users
        ->withCount(['order' => function($query) use ($branch_id, $from, $to) {
            if ($branch_id) {
                $query->where("branch_id", $branch_id);
            }
            if ($from) {
                $query->where("created_at", ">=", $from); 
            }
            if ($to) {
                $query->where("created_at", "<=", $to);
            }
        }]) 
        ->get();
        $top_customers = $users
        ->sortByDesc('order_count')->values();
        $recent_orders = 
        $all_orders = $this->orders
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc('id')
        ->limit(10)
        ->get();

        return response()->json([ 
            'order_statistics' => $order_statistics,
            'earning_statistics' => $earning_statistics,
            'recent_orders' => $all_orders,
            'top_selling' => $top_selling,
            'offers' => $deals,
            'top_customers' => $top_customers,
            'msg_package' => $msg_package,
        ]);
    }
}
