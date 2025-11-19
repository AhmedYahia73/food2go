<?php

namespace App\Http\Controllers\api\branch\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

use App\Models\SmsIntegration; 
use App\Models\SmsBalance; 
use App\Models\Order;
use App\Models\Product;
use App\Models\Deal;
use App\Models\User;
use App\Models\Setting;
use App\Models\TimeSittings;
use App\Models\LogOrder;

class HomeController extends Controller
{
    public function __construct(private Order $orders, private Product $products,
    private Deal $deals, private User $users, private Setting $settings
    , private TimeSittings $TimeSittings, private LogOrder $log_order
    , private SmsBalance $sms_balance, private SmsIntegration $sms_integration,){}

    public function home_orders_count(Request $request){ 
        
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
        ->where('branch_id', $request->user()->id)
        ->count(); 
        $pending = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'pending') 
        ->where('branch_id', $request->user()->id)
        ->count();
        $confirmed = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'confirmed') 
        ->where('branch_id', $request->user()->id)
        ->count();
        $processing = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'processing') 
        ->where('branch_id', $request->user()->id)
        ->count();
        $out_for_delivery = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })  
        ->where('order_status', 'out_for_delivery') 
        ->where('branch_id', $request->user()->id)
        ->count();
        $delivered = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })  
        ->where('order_status', 'delivered') 
        ->where('branch_id', $request->user()->id)
        ->count();
        $returned = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'returned') 
        ->where('branch_id', $request->user()->id)
        ->count();
        $faild_to_deliver = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'faild_to_deliver') 
        ->where('branch_id', $request->user()->id)
        ->count();
        $canceled = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'canceled') 
        ->where('branch_id', $request->user()->id)
        ->count();
        $scheduled = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'scheduled') 
        ->where('branch_id', $request->user()->id)
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
        $this->log_order
        ->whereDate('created_at', '<=', now()->subDays(14))
        ->delete();

        $currentYear = Carbon::now()->year; 
        $all_orders = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('branch_id', $request->user()->id)
        ->orderByDesc('id')
        ->get();
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
        $products = $this->products
        ->orderBy('order')
        ->get();
        $top_selling = $products
        ->sortByDesc('orders_count_branch');
        $users = $this->users
        ->get();
        $top_customers = $users
        ->sortByDesc('orders_count')->values();
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

        return response()->json([
            'order_statistics' => $order_statistics,
            'earning_statistics' => $earning_statistics,
            'recent_orders' => $all_orders,
            'top_selling' => $top_selling, 
            'top_customers' => $top_customers,
            'offers' => $deals,
        ]);
    }
}
