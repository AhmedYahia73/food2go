<?php

namespace App\Http\Controllers\api\branch\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;

use App\Models\SmsIntegration; 
use App\Models\SmsBalance; 
use App\Models\Order;
use App\Models\Product;
use App\Models\Deal;
use App\Models\User;
use App\Models\Setting;
use App\Models\TimeSittings;
use App\Models\LogOrder;
use App\Models\CafeTable;
use App\Models\CashierMan;
use App\Models\OrderCart;
use App\Models\OrderDetail;
use App\Models\OrderFinancial;
use App\Models\Expense;
use App\Models\BranchOffProduct;

class HomeController extends Controller
{
    public function __construct(
        private Order $orders, 
        private Product $products,
        private Deal $deals, 
        private User $users, 
        private Setting $settings, 
        private TimeSittings $TimeSittings, 
        private LogOrder $log_order, 
        private SmsBalance $sms_balance, 
        private SmsIntegration $sms_integration,
        private ?BranchOffProduct $branch_off = null
    ){
        $this->branch_off = $branch_off ?? new BranchOffProduct();
    }

    private function getSmsPackageInfo()
    {
        try {
            $response = Http::timeout(3)->get('https://clientbcknd.food2go.online/admin/v1/my_sms_package')->body();
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
                $msg_number = $this->sms_balance->create([
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
            return env('APP_NAME') == 'Lamada' ? false : $msg_package;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getOperatingDateRange(Request $request, $branch_id)
    {
        $from_date = $request->from ?? date("Y-m-d");
        $to_date = $request->to ?? date("Y-m-d");

        $time_sittings = $this->TimeSittings->where('branch_id', $branch_id)->get();

        if ($time_sittings->count() > 0) {
            $from = $time_sittings[0];
            $to = $time_sittings[$time_sittings->count() - 1];

            $from_time = is_object($from) ? $from->from : '00:00:00';
            $to_time = is_object($to) ? $to->from : '23:59:59';
            $hours = is_object($to) ? intval($to->hours) : 0;
            $minutes = is_object($to) ? intval($to->minutes) : 0;

            $start = Carbon::parse($from_date . ' ' . $from_time);
            $end = Carbon::parse($to_date . ' ' . $to_time)->addHours($hours)->addMinutes($minutes);

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

        return [$start, $end];
    }

    /**
     * ✅ Matches product sales calculation from product_report exactly
     */
    private function getProductSalesFromOrders($start = null, $end = null, $branch_id = null, $only_online = false, $limit = 5)
    {
        $ordersQuery = Order::query()
            ->where("is_void", 0)
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->where(function($query) {
                $query->where('status', 1)->orWhereNull('status');
            })
            ->where(function($query) {
                $query->where('order_active', 1)->orWhereNull('order_active');
            });

        if ($only_online) {
            $ordersQuery->where('pos', 0);
        }
        if ($branch_id) {
            $ordersQuery->where('branch_id', $branch_id);
        }
        if ($start) {
            $ordersQuery->where('created_at', '>=', $start);
        }
        if ($end) {
            $ordersQuery->where('created_at', '<=', $end);
        }

        $orders = $ordersQuery->get(["id", "order_details"]);

        $products = [];
        foreach ($orders as $orderItem) {
            $details = $orderItem->order_details_data;
            if (!empty($details) && is_array($details)) {
                foreach ($details as $element) {
                    if (!is_array($element)) continue;

                    $price = 0;
                    if (isset($element['variations']) && is_array($element['variations'])) {
                        foreach ($element['variations'] as $val) {
                            if (isset($val['options']) && is_array($val['options'])) {
                                foreach ($val['options'] as $option) {
                                    if (!is_array($option)) continue;
                                    $opt_price = (float)($option['price'] ?? 0);
                                    $opt_discount = isset($option['after_disount'])
                                        ? (float)$option['after_disount']
                                        : (isset($option['price_after_discount']) ? (float)$option['price_after_discount'] : $opt_price);
                                    $opt_tax = isset($option['price_after_tax']) ? (float)$option['price_after_tax'] : $opt_price;
                                    $opt_final = isset($option['final_price']) ? (float)$option['final_price'] : ($opt_tax - $opt_price + $opt_discount);
                                    $price += $opt_final;
                                }
                            }
                        }
                    }

                    if (isset($element['extras']) && is_array($element['extras'])) {
                        foreach ($element['extras'] as $extra) {
                            if (!is_array($extra)) continue;
                            $extra_price = (float)($extra['price'] ?? 0);
                            $extra_discount = isset($extra['price_after_discount'])
                                ? (float)$extra['price_after_discount']
                                : (isset($extra['after_disount']) ? (float)$extra['after_disount'] : $extra_price);
                            $extra_tax = isset($extra['price_after_tax']) ? (float)$extra['price_after_tax'] : $extra_price;
                            $extra_final = isset($extra['final_price']) ? (float)$extra['final_price'] : ($extra_tax - $extra_price + $extra_discount);
                            $price += $extra_final;
                        }
                    }

                    if (isset($element['product'][0]['product']) && is_array($element['product'][0]['product'])) {
                        $prod = $element['product'][0]['product'];
                        $product_id = $prod['id'] ?? null;
                        if (!$product_id) continue;

                        $prod_price = (float)($prod['price'] ?? 0);
                        $prod_discount = isset($prod['price_after_discount']) && $prod['price_after_discount'] !== null
                            ? (float)$prod['price_after_discount']
                            : (isset($prod['after_disount']) ? (float)$prod['after_disount'] : $prod_price);
                        $prod_tax = isset($prod['price_after_tax']) && $prod['price_after_tax'] !== null
                            ? (float)$prod['price_after_tax']
                            : $prod_price;
                        $prod_final = isset($prod['final_price']) && $prod['final_price'] !== null
                            ? (float)$prod['final_price']
                            : ($prod_tax - $prod_price + $prod_discount);

                        $price += $prod_final;
                        $count = (int)($element['product'][0]['count'] ?? 1);
                        if ($count <= 0) $count = 1;

                        if (isset($products[$product_id])) {
                            $products[$product_id]["total_amount"] += $price * $count;
                            $products[$product_id]["total_sales"] += $count;
                        } else {
                            $products[$product_id] = [
                                "product_id" => $product_id,
                                "total_sales" => $count,
                                "total_amount" => $price * $count,
                                "product" => [
                                    "id" => $product_id,
                                    "name" => $prod['name'] ?? ("Product " . $product_id),
                                ],
                            ];
                        }
                    }
                }
            }
        }

        // Enrich with product model info (image_link, description, name, etc.)
        $prod_ids = array_keys($products);
        if (!empty($prod_ids)) {
            $product_models = Product::whereIn('id', $prod_ids)->get()->keyBy('id');
            foreach ($products as $pId => &$pData) {
                if (isset($product_models[$pId])) {
                    $m = $product_models[$pId];
                    $pData["id"] = $m->id;
                    $pData["name"] = $m->name;
                    $pData["image_link"] = $m->image_link;
                    $pData["description"] = $m->description ?? '';
                    $pData["price"] = $m->price;
                    $pData["order_count"] = $pData["total_sales"];
                    $pData["product"] = [
                        "id" => $m->id,
                        "name" => $m->name,
                        "image_link" => $m->image_link,
                    ];
                } else {
                    $pData["id"] = $pId;
                    $pData["image_link"] = null;
                    $pData["description"] = "";
                    $pData["price"] = $pData["total_amount"];
                    $pData["order_count"] = $pData["total_sales"];
                }
            }
            unset($pData);
        }

        $collection = collect(array_values($products))->sortByDesc("total_sales")->values();
        if ($limit) {
            $collection = $collection->take($limit);
        }
        return $collection;
    }

    public function branch_product_status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean', 
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        if ($request->status) {
            $this->branch_off
            ->where('branch_id', $request->user()->id)
            ->where('product_id', $id)
            ->delete();
        } 
        else {
            $this->branch_off
            ->create([
                'branch_id' => $request->user()->id,
                'product_id' => $id
            ]);
        }
        
        return response()->json([
            'success' => 'You change status success'
        ]);
    }

    /**
     * ✅ NEW UNIFIED OVERVIEW API FOR BRANCH:
     * GET /branch/home/overview
     */
    public function overview(Request $request)
    {
        $branch_id = $request->user()->id;
        list($start, $end) = $this->getOperatingDateRange($request, $branch_id);

        $only_online = $request->boolean('only_online') || $request->boolean('is_online') || $request->pos === '0' || $request->pos === 0;

        $msg_package = $this->getSmsPackageInfo();

        // Active Orders & Occupied Tables KPIs
        $occupied_tables = CafeTable::whereIn("current_status", [
            "not_available_pre_order", 
            "not_available_with_order", 
            "not_available_but_checkout"
        ])->where("branch_id", $branch_id)->count();

        $table_active_orders = CafeTable::whereIn("current_status", ["not_available_with_order"])
            ->where("branch_id", $branch_id)->count();

        $tables_ids = CafeTable::whereIn("current_status", ["not_available_pre_order", "not_available_with_order"])
            ->where("branch_id", $branch_id)->pluck("id")->toArray();

        $active_cart_amount = floatval(OrderCart::whereIn("table_id", $tables_ids)->where("branch_id", $branch_id)->sum("amount"));

        $in_progress_query = $this->orders->query()
            ->where('branch_id', $branch_id)
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery'])
            ->where(function($q) {
                $q->where('status', 1)->orWhereNull('status');
            });
        if ($only_online) $in_progress_query->where('pos', 0);
        $in_progress_count = $in_progress_query->count();
        $in_progress_amount = floatval($in_progress_query->sum('amount'));

        $active_orders = $in_progress_count + $table_active_orders;
        $active_amount = number_format($in_progress_amount + $active_cart_amount, 2, '.', '');

        $online_cashiers = CashierMan::whereHas("tokens")->where("branch_id", $branch_id)->count();
        $offline_cashiers = CashierMan::whereDoesntHave("tokens")->where("branch_id", $branch_id)->count();

        // Orders Summary
        $baseOrdersQuery = function($withDates = false) use ($branch_id, $only_online, $start, $end) {
            $q = $this->orders->query()->where('branch_id', $branch_id);
            if ($only_online) $q->where('pos', 0);
            if ($withDates) {
                $q->where('created_at', '>=', $start)->where('created_at', '<=', $end);
            }
            return $q->where(function($query) {
                $query->where('status', 1)->orWhereNull('status');
            });
        };

        $periodOrdersQuery = $baseOrdersQuery(true);
        $orders_summary = [
            'orders' => (clone $periodOrdersQuery)->count(),
            'pending' => (clone $periodOrdersQuery)->where('order_status', 'pending')->count(),
            'confirmed' => (clone $periodOrdersQuery)->where('order_status', 'confirmed')->count(),
            'processing' => (clone $periodOrdersQuery)->where('order_status', 'processing')->count(),
            'out_for_delivery' => (clone $periodOrdersQuery)->where('order_status', 'out_for_delivery')->count(),
            'delivered' => (clone $periodOrdersQuery)->where('order_status', 'delivered')->count(),
            'returned' => (clone $periodOrdersQuery)->where('order_status', 'returned')->count(),
            'faild_to_deliver' => (clone $periodOrdersQuery)->where('order_status', 'faild_to_deliver')->count(),
            'canceled' => (clone $periodOrdersQuery)->where('order_status', 'canceled')->count(),
            'scheduled' => (clone $periodOrdersQuery)->where('order_status', 'scheduled')->count(),
            'refund' => (clone $periodOrdersQuery)->where('order_status', 'refund')->count(),
            'online_orders' => (clone $periodOrdersQuery)->where('pos', 0)->count(),
            'pos_orders' => (clone $periodOrdersQuery)->where('pos', 1)->count(),
        ];

        // Hourly Series
        // Hourly Series (Aligned to 24 hours of the operating period)
        $orders_hourly_raw = Order::selectRaw("
            HOUR(created_at) as hour_num,
            order_type,
            order_status,
            payment_status,
            payment_method_id,
            is_void,
            amount,
            total_discount
        ")
        ->where('branch_id', $branch_id)
        ->where("created_at", ">=", $start)->where("created_at", "<=", $end);
        if ($only_online) $orders_hourly_raw->where('pos', 0);
        $orders_period = $orders_hourly_raw->get();

        $hours_24 = [];
        for ($h = 0; $h < 24; $h++) {
            $formatted_label = Carbon::createFromTime($h, 0)->format('h:00 A');
            $short_label = Carbon::createFromTime($h, 0)->format('h A');
            $hours_24[$h] = [
                'hour' => $h,
                'label' => $formatted_label,
                'short_label' => $short_label,
                'orders_count' => 0,
                'net_sales' => 0.0,
                'net_payments' => 0.0,
                'return_amount' => 0.0,
                'discount_amount' => 0.0,
                'dine_in' => 0,
                'delivery' => 0,
                'takeaway' => 0,
                'drive_thru' => 0,
            ];
        }

        foreach ($orders_period as $ord) {
            $h = intval($ord->hour_num);
            if (!isset($hours_24[$h])) continue;

            $amt = floatval($ord->amount ?? 0);
            $disc = floatval($ord->total_discount ?? 0);
            $isVoid = (int)($ord->is_void ?? 0) === 1;
            $status = strtolower(trim($ord->order_status ?? ''));
            $isReturn = $isVoid || in_array($status, ['returned', 'refund']);
            $isCanceled = in_array($status, ['canceled', 'faild_to_deliver']);

            if ($isReturn) {
                $hours_24[$h]['return_amount'] += $amt;
            } elseif (!$isCanceled) {
                $hours_24[$h]['orders_count'] += 1;
                $hours_24[$h]['net_sales'] += $amt;
                $hours_24[$h]['discount_amount'] += $disc;

                if ($ord->payment_status === 'paid' || !empty($ord->payment_method_id)) {
                    $hours_24[$h]['net_payments'] += $amt;
                }

                $type = strtolower(trim($ord->order_type ?? ''));
                if ($type === 'dine_in') {
                    $hours_24[$h]['dine_in'] += 1;
                } elseif ($type === 'delivery') {
                    $hours_24[$h]['delivery'] += 1;
                } elseif (in_array($type, ['takeaway', 'take_away', 'pickup'])) {
                    $hours_24[$h]['takeaway'] += 1;
                } elseif (in_array($type, ['drive_thru', 'drive_through'])) {
                    $hours_24[$h]['drive_thru'] += 1;
                }
            }
        }

        $sales_hourly = [];
        $payments_hourly = [];
        $return_hourly = [];
        $discount_hourly = [];
        $order_types = [];
        foreach ($hours_24 as $hData) {
            $sales_hourly[] = ['hour' => $hData['label'], 'total_amount' => $hData['net_sales']];
            $payments_hourly[] = ['hour' => $hData['label'], 'total_amount' => $hData['net_payments']];
            $return_hourly[] = ['hour' => $hData['label'], 'total_amount' => $hData['return_amount']];
            $discount_hourly[] = ['hour' => $hData['label'], 'total_discount' => $hData['discount_amount']];
            $order_types[] = ['hour' => $hData['label'], 'order_type' => 'dine_in', 'order_count' => $hData['dine_in']];
            $order_types[] = ['hour' => $hData['label'], 'order_type' => 'delivery', 'order_count' => $hData['delivery']];
            $order_types[] = ['hour' => $hData['label'], 'order_type' => 'take_away', 'order_count' => $hData['takeaway']];
            $order_types[] = ['hour' => $hData['label'], 'order_type' => 'drive_thru', 'order_count' => $hData['drive_thru']];
        }

        $expenses_hourly = Expense::selectRaw("DATE_FORMAT(created_at, '%h:00 %p') as hour, sum(amount) as total_amount")
            ->where('branch_id', $branch_id)
            ->groupBy("hour")
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end)
            ->get();

        // Top Products (uses exact sales logic from product_report)
        $top_product = $this->getProductSalesFromOrders($start, $end, $branch_id, $only_online, 5);

        $top_financial = OrderFinancial::selectRaw("financial_id, sum(amount) as total_amount")
            ->whereHas("order", function($query) use ($branch_id, $only_online, $start, $end){
                if ($only_online) $query->where('orders.pos', 0);
                $query->where("orders.branch_id", $branch_id);
                $query->where("orders.created_at", ">=", $start)->where("orders.created_at", "<=", $end);
            })
            ->groupBy("financial_id")
            ->with("financials:id,name") 
            ->orderByDesc("total_amount")
            ->limit(5)
            ->get();

        $top_payment_method_query = Order::selectRaw("payment_method_id, sum(amount) as total_amount")
            ->whereNotNull("payment_method_id")
            ->where('branch_id', $branch_id)
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end)
            ->where(function($query){
                $query->where("order_status", "!=", "returned")
                      ->where("order_status", "!=", "refund")
                      ->where("is_void", 0);
            })
            ->groupBy("payment_method_id")
            ->with("payment_method:id,name")
            ->orderByDesc("total_amount")
            ->limit(5);
        if ($only_online) $top_payment_method_query->where('pos', 0);
        $top_payment_method = $top_payment_method_query->get();

        // Monthly Statistics
        $currentYear = Carbon::now()->year;
        $monthly_orders_query = $this->orders->select('created_at', 'amount')
            ->where('branch_id', $branch_id)
            ->whereYear('created_at', $currentYear)
            ->where(function($query) {
                $query->where('status', 1)->orWhereNull('status');
            })
            ->where(function($query){
                $query->where("order_status", "!=", "returned")
                      ->where("order_status", "!=", "refund")
                      ->where("is_void", 0);
            });
        if ($only_online) $monthly_orders_query->where('pos', 0);
        $year_orders = $monthly_orders_query->get();

        $months = ['Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6,
                   'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12];
        $order_statistics = [];
        $earning_statistics = [];
        foreach ($months as $m_name => $m_num) {
            $m_items = $year_orders->filter(function($ord) use ($m_num) {
                return Carbon::parse($ord->created_at)->month === $m_num;
            });
            $order_statistics[$m_name] = $m_items->count();
            $earning_statistics[$m_name] = floatval($m_items->sum('amount'));
        }

        // Recent Orders
        $recent_orders_query = $this->orders
            ->where('branch_id', $branch_id)
            ->with(['branch:id,name', 'user:id,name,phone'])
            ->where(function($query) {
                $query->where('status', 1)->orWhereNull('status');
            })
            ->orderByDesc("created_at")
            ->limit(10);
        if ($only_online) $recent_orders_query->where('pos', 0);
        $recent_orders = $recent_orders_query->get();

        // Top Selling (matches product_report) & Customers & Deals
        $top_selling = $this->getProductSalesFromOrders($start, $end, $branch_id, $only_online, 20);

        $today = Carbon::now()->format('l');
        $deals = $this->deals
            ->with('times')
            ->where('status', 1)
            ->where('start_date', '<=', date('Y-m-d'))
            ->where('end_date', '>=', date('Y-m-d'))
            ->where(function($q) use ($today) {
                $q->where('daily', 1)
                  ->orWhereHas('times', function($query) use ($today) {
                      $query->where('day', $today)
                            ->where('from', '<=', now()->format('H:i:s'))
                            ->where('to', '>=', now()->format('H:i:s'));
                  });
            })
            ->get();

        $top_customers = $this->users
            ->withCount(['order' => function($query) use ($branch_id, $start, $end, $only_online) {
                if ($only_online) $query->where('orders.pos', 0);
                $query->where("orders.branch_id", $branch_id);
                $query->where("orders.created_at", ">=", $start)->where("orders.created_at", "<=", $end);
            }])
            ->get()
            ->sortByDesc('order_count')
            ->values();

        return response()->json([
            "kpis" => [
                "active_orders" => $active_orders,
                "active_amount" => $active_amount,
                "occupied_tables" => $occupied_tables,
                "online_cashiers" => $online_cashiers,
                "offline_cashiers" => $offline_cashiers,
                "total_orders" => $orders_summary['orders'],
            ],
            "active_orders" => $active_orders,
            "active_amount" => $active_amount,
            "occupied_tables" => $occupied_tables,
            "online_cashiers" => $online_cashiers,
            "offline_cashiers" => $offline_cashiers,
            "orders_summary" => $orders_summary,
            "orders_count" => $orders_summary,
            "order_statistics" => $order_statistics,
            "earning_statistics" => $earning_statistics,
            "monthly_statistics" => [
                "order_statistics" => $order_statistics,
                "earning_statistics" => $earning_statistics,
            ],
            "top_product" => $top_product,
            "top_financial" => $top_financial,
            "top_payment_method" => $top_payment_method,
            "hourly_timeline" => array_values($hours_24),
            "branch_sales" => [],
            "order_types" => $order_types,
            "sales_hourly" => $sales_hourly,
            "payments_hourly" => $payments_hourly,
            "return_hourly" => $return_hourly,
            "discount_hourly" => $discount_hourly,
            "expenses_hourly" => $expenses_hourly,
            "recent_orders" => $recent_orders,
            "top_selling" => $top_selling,
            "offers" => $deals,
            "top_customers" => $top_customers,
            "msg_package" => $msg_package,
        ]);
    }

    /**
     * ✅ ADD home_data for branch
     */
    public function home_data(Request $request)
    {
        $branch_id = $request->user()->id;
        list($start, $end) = $this->getOperatingDateRange($request, $branch_id);

        $from = $request->filled('from') ? $start->format('Y-m-d H:i:s') : null;
        $to = $request->filled('to') ? $end->format('Y-m-d H:i:s') : null;

        $only_online = $request->boolean('only_online') || $request->boolean('is_online') || $request->pos === '0' || $request->pos === 0;

        $occupied_tables = CafeTable::whereIn("current_status", [
            "not_available_pre_order", 
            "not_available_with_order", 
            "not_available_but_checkout"
        ])->where("branch_id", $branch_id)->count();

        $table_active_orders = CafeTable::whereIn("current_status", ["not_available_with_order"])
            ->where("branch_id", $branch_id)->count();

        $tables_ids = CafeTable::whereIn("current_status", ["not_available_pre_order", "not_available_with_order"])
            ->where("branch_id", $branch_id)->pluck("id")->toArray();

        $active_cart_amount = floatval(OrderCart::whereIn("table_id", $tables_ids)->where("branch_id", $branch_id)->sum("amount"));

        $in_progress_query = $this->orders->query()
            ->where('branch_id', $branch_id)
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery'])
            ->where(function($q) {
                $q->where('status', 1)->orWhereNull('status');
            });
        if ($only_online) $in_progress_query->where('pos', 0);
        $in_progress_count = $in_progress_query->count();
        $in_progress_amount = floatval($in_progress_query->sum('amount'));

        $active_orders = $in_progress_count + $table_active_orders;
        $active_amount = number_format($in_progress_amount + $active_cart_amount, 2, '.', '');

        $online_cashiers = CashierMan::whereHas("tokens")->where("branch_id", $branch_id)->count();
        $offline_cashiers = CashierMan::whereDoesntHave("tokens")->where("branch_id", $branch_id)->count();

        // Top Products (uses exact sales logic from product_report)
        $top_product = $this->getProductSalesFromOrders($start, $end, $branch_id, $only_online, 5);

        $top_financial = OrderFinancial::selectRaw("financial_id, sum(amount) as total_amount")
            ->whereHas("order", function($query) use ($branch_id, $only_online, $start, $end){
                if ($only_online) $query->where('orders.pos', 0);
                $query->where("orders.branch_id", $branch_id);
                $query->where("orders.created_at", ">=", $start)->where("orders.created_at", "<=", $end);
            })
            ->groupBy("financial_id")
            ->with("financials:id,name") 
            ->limit(5)->get();

        $top_payment_method_query = Order::selectRaw("payment_method_id, sum(amount) as total_amount")
            ->whereNotNull("payment_method_id")
            ->where('branch_id', $branch_id)
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end)
            ->where(function($query){
                $query->where("order_status", "!=", "returned")
                      ->where("order_status", "!=", "refund")
                      ->where("is_void", 0);
            })
            ->groupBy("payment_method_id")
            ->with("payment_method:id,name")
            ->orderByDesc("total_amount")
            ->limit(5);
        if ($only_online) $top_payment_method_query->where('pos', 0);
        $top_payment_method = $top_payment_method_query->get();

        $order_types_query = Order::selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, count(id) as order_count, order_type")
            ->where('branch_id', $branch_id)
            ->groupBy("hour")->groupBy("order_type")
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end)
            ->where(function($query){
                $query->where("order_status", "!=", "returned")
                      ->where("order_status", "!=", "refund")
                      ->where("is_void", 0);
            });
        if ($only_online) $order_types_query->where('pos', 0);
        $order_types = $order_types_query->get();

        $sales_hourly_query = Order::selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, sum(amount) as total_amount")
            ->where('branch_id', $branch_id)
            ->groupBy("hour")
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end)
            ->where(function($query){
                $query->where("order_status", "!=", "returned")
                      ->where("order_status", "!=", "refund")
                      ->where("is_void", 0);
            });
        if ($only_online) $sales_hourly_query->where('pos', 0);
        $sales_hourly = $sales_hourly_query->get();

        $payments_hourly_query = Order::selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, sum(amount) as total_amount")
            ->where('branch_id', $branch_id)
            ->groupBy("hour")
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end)
            ->where(function($query){
                $query->where("order_status", "!=", "returned")
                      ->where("order_status", "!=", "refund")
                      ->where("is_void", 0);
            })
            ->where(function($query){
                $query->where('payment_status', 'paid')
                      ->orWhereNotNull('payment_method_id');
            });
        if ($only_online) $payments_hourly_query->where('pos', 0);
        $payments_hourly = $payments_hourly_query->get();

        $return_hourly_query = Order::selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, sum(amount) as total_amount")
            ->where('branch_id', $branch_id)
            ->groupBy("hour")
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end)
            ->where(function($query){
                $query->where("order_status", "returned")
                      ->orWhere("order_status", "refund")
                      ->orWhere("is_void", 1);
            });
        if ($only_online) $return_hourly_query->where('pos', 0);
        $return_hourly = $return_hourly_query->get();

        $discount_hourly_query = Order::selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, sum(total_discount) as total_discount")
            ->where('branch_id', $branch_id)
            ->groupBy("hour")
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end)
            ->where(function($query){
                $query->where("order_status", "!=", "returned")
                      ->where("order_status", "!=", "refund")
                      ->where("is_void", 0);
            });
        if ($only_online) $discount_hourly_query->where('pos', 0);
        $discount_hourly = $discount_hourly_query->get();

        $expenses_hourly = Expense::selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, sum(amount) as total_amount")
            ->where('branch_id', $branch_id)
            ->groupBy("hour")
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end)
            ->get();

        return response()->json([
            "online_cashiers" => $online_cashiers,
            "offline_cashiers" => $offline_cashiers,
            "active_amount" => $active_amount,
            "occupied_tables" => $occupied_tables,
            "active_orders" => $active_orders,
            "top_product" => $top_product,
            "top_financial" => $top_financial,
            "top_payment_method" => $top_payment_method,
            "order_types" => $order_types,
            "sales_hourly" => $sales_hourly,
            "payments_hourly" => $payments_hourly,
            "return_hourly" => $return_hourly,
            "discount_hourly" => $discount_hourly,
            "branch_sales" => [],
            "expenses_hourly" => $expenses_hourly,
        ]);
    }

    /**
     * ✅ FIXED OLD API:
     * GET /branch/home/orders_count
     */
    public function home_orders_count(Request $request = null){ 
        if (!$request) {
            $request = request();
        }

        $branch_id = $request->user()->id;
        $msg_package = $this->getSmsPackageInfo();

        $only_online = $request->boolean('only_online') || $request->boolean('is_online') || $request->pos === '0' || $request->pos === 0;

        $baseOrderQuery = function() use ($branch_id, $only_online, $request) {
            $q = $this->orders->query()->where('branch_id', $branch_id);
            if ($only_online) {
                $q->where('pos', 0);
            }
            if ($request->filled('from')) {
                $q->where('created_at', '>=', $request->from . ' 00:00:00');
            }
            if ($request->filled('to')) {
                $q->where('created_at', '<=', $request->to . ' 23:59:59');
            }
            return $q->where(function($query) {
                $query->where('status', 1)->orWhereNull('status');
            });
        };

        $orders = $baseOrderQuery()->count(); 
        $pending = $baseOrderQuery()->where('order_status', 'pending')->count();
        $confirmed = $baseOrderQuery()->where('order_status', 'confirmed')->count();
        $processing = $baseOrderQuery()->where('order_status', 'processing')->count();
        $out_for_delivery = $baseOrderQuery()->where('order_status', 'out_for_delivery')->count();
        $delivered = $baseOrderQuery()->where('order_status', 'delivered')->count();
        $returned = $baseOrderQuery()->where('order_status', 'returned')->count();
        $faild_to_deliver = $baseOrderQuery()->where('order_status', 'faild_to_deliver')->count();
        $canceled = $baseOrderQuery()->where('order_status', 'canceled')->count();
        $scheduled = $baseOrderQuery()->where('order_status', 'scheduled')->count();
        $refund = $baseOrderQuery()->where('order_status', 'refund')->count();

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
            'refund' => $refund,
            'online_orders' => (clone $baseOrderQuery())->where('pos', 0)->count(),
            'pos_orders' => (clone $baseOrderQuery())->where('pos', 1)->count(),
            'msg_package' => $msg_package
        ];

        return response()->json($orders_count);
    }

    /**
     * ✅ FIXED OLD API:
     * GET /branch/home
     */
    public function home(Request $request){
        $branch_id = $request->user()->id;
        $this->log_order
        ->whereDate('created_at', '<=', now()->subDays(14))
        ->delete();

        $currentYear = Carbon::now()->year; 
        $only_online = $request->boolean('only_online') || $request->boolean('is_online') || $request->pos === '0' || $request->pos === 0;

        $all_orders_query = $this->orders 
        ->where('branch_id', $branch_id)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc("created_at");

        if ($only_online) {
            $all_orders_query->where('pos', 0);
        }
        if ($request->filled('from')) {
            $all_orders_query->where('created_at', '>=', $request->from . ' 00:00:00');
        }
        if ($request->filled('to')) {
            $all_orders_query->where('created_at', '<=', $request->to . ' 23:59:59');
        }
        $all_orders = $all_orders_query->get();

        $order_statistics = [];
        $earning_statistics = [];
        $months = ['Jan' => 1, 'Feb' => 2, 'Mar' => 3, 'Apr' => 4, 'May' => 5, 'Jun' => 6,
                   'Jul' => 7, 'Aug' => 8, 'Sep' => 9, 'Oct' => 10, 'Nov' => 11, 'Dec' => 12];
        foreach ($months as $m_name => $m_num) {
            $m_items = $all_orders->filter(function($ord) use ($m_num, $currentYear) {
                $c = Carbon::parse($ord->created_at);
                return $c->year === $currentYear && $c->month === $m_num;
            });
            $order_statistics[$m_name] = $m_items->count();
            $earning_statistics[$m_name] = floatval($m_items->sum('amount'));
        }

        // Top Selling (matches product_report)
        $top_selling = $this->getProductSalesFromOrders(
            $request->filled('from') ? $request->from . ' 00:00:00' : null,
            $request->filled('to') ? $request->to . ' 23:59:59' : null,
            $branch_id,
            $only_online,
            20
        );

        $top_customers = $this->users
        ->withCount(['order' => function($query) use ($branch_id, $only_online, $request) {
            if ($only_online) $query->where('orders.pos', 0);
            $query->where("orders.branch_id", $branch_id);
            if ($request->filled('from')) $query->where('orders.created_at', '>=', $request->from . ' 00:00:00');
            if ($request->filled('to')) $query->where('orders.created_at', '<=', $request->to . ' 23:59:59');
        }])
        ->get()
        ->sortByDesc('order_count')->values();

        $today = Carbon::now()->format('l');
        $deals = $this->deals
        ->with('times')
        ->where('status', 1)
        ->where('start_date', '<=', date('Y-m-d'))
        ->where('end_date', '>=', date('Y-m-d'))
        ->where(function($q) use ($today) {
            $q->where('daily', 1)
              ->orWhereHas('times', function($query) use ($today) {
                  $query->where('day', $today)
                        ->where('from', '<=', now()->format('H:i:s'))
                        ->where('to', '>=', now()->format('H:i:s'));
              });
        })
        ->get();

        $msg_package = $this->getSmsPackageInfo();

        return response()->json([
            'order_statistics' => $order_statistics,
            'earning_statistics' => $earning_statistics,
            'recent_orders' => $all_orders->take(10)->values(),
            'top_selling' => $top_selling, 
            'top_customers' => $top_customers,
            'offers' => $deals,
            'msg_package' => $msg_package,
        ]);
    }
}
