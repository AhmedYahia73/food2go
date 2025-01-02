<?php

namespace App\Http\Controllers\api\admin\home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\Product;
use App\Models\Deal;
use App\Models\User;

class HomeController extends Controller
{
    public function __construct(private Order $orders, private Product $products,
    private Deal $deals, private User $users){}

    public function home(){
        // https://bcknd.food2go.online/admin/home
        $orders = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
        ->count();
        $pending = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'pending')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
        ->count();
        $confirmed = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'confirmed')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
        ->count();
        $processing = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'processing')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
        ->count();
        $out_for_delivery = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'out_for_delivery')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
        ->count();
        $delivered = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'delivered')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
        ->count();
        $returned = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'returned')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
        ->count();
        $faild_to_deliver = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'faild_to_deliver')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
        ->count();
        $canceled = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'canceled')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
        ->count();
        $scheduled = $this->orders
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'scheduled')
        ->orderByDesc('id')
        ->with(['user', 'branch', 'delivery'])
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
        ];
        $currentYear = Carbon::now()->year; 
        $all_orders = $this->orders 
        ->where('pos', 0)
        ->where('pos', 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
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
        ->get();
        $top_selling = $products
        ->sortByDesc('orders_count');       
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
        ->get();
        $top_customers = $users
        ->sortByDesc('orders_count');

        return response()->json([
            'orders' => $orders_count,
            'order_statistics' => $order_statistics,
            'earning_statistics' => $earning_statistics,
            'recent_orders' => $all_orders,
            'top_selling' => $top_selling,
            'offers' => $deals,
            'top_customers' => $top_customers,
        ]);
    }
}
