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
use App\Models\Notification;
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
    public function __construct(
        private Order $orders, 
        private Product $products,
        private Deal $deals, 
        private User $users, 
        private Setting $settings, 
        private SmsIntegration $sms_integration, 
        private SmsBalance $sms_balance,
        private TimeSittings $TimeSittings, 
        private LogOrder $log_order
    ){}

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

    private function getOperatingDateRange(Request $request, $branch_id = null)
    {
        $from_date = $request->from ?? date("Y-m-d");
        $to_date = $request->to ?? date("Y-m-d");

        $time_sittings_query = $this->TimeSittings->query();
        if ($branch_id) {
            $time_sittings_query->where('branch_id', $branch_id);
        }
        $time_sittings = $time_sittings_query->get();

        if ($time_sittings->count() > 0) {
            $items = [];
            $count = 0;
            $to = $time_sittings[0]; 
            $from = $time_sittings[0];
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

    /**
     * ✅ NEW UNIFIED OVERVIEW API:
     * GET /admin/home/overview
     * Returns consolidated, accurate dashboard overview metrics
     */
    public function overview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'from' => 'date',
            'to' => 'date',
            'branch_id' => 'exists:branches,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        $branch_id = $request->branch_id;
        list($start, $end) = $this->getOperatingDateRange($request, $branch_id);

        // Do NOT filter by pos=0 unless explicitly requested as online-only
        $only_online = $request->boolean('only_online') || $request->boolean('is_online') || $request->pos === '0' || $request->pos === 0;

        // 1. SMS Info
        $msg_package = $this->getSmsPackageInfo();

        // 2. Active Orders & Occupied Tables KPIs
        $occupied_tables_query = CafeTable::whereIn("current_status", [
            "not_available_pre_order", 
            "not_available_with_order", 
            "not_available_but_checkout"
        ]);
        if ($branch_id) $occupied_tables_query->where("branch_id", $branch_id);
        $occupied_tables = $occupied_tables_query->count();

        $table_active_orders_query = CafeTable::whereIn("current_status", ["not_available_with_order"]);
        if ($branch_id) $table_active_orders_query->where("branch_id", $branch_id);
        $table_active_orders = $table_active_orders_query->count();

        $tables_ids_query = CafeTable::whereIn("current_status", ["not_available_pre_order", "not_available_with_order"]);
        if ($branch_id) $tables_ids_query->where("branch_id", $branch_id);
        $tables_ids = $tables_ids_query->pluck("id")->toArray();

        $active_cart_amount_query = OrderCart::whereIn("table_id", $tables_ids);
        if ($branch_id) $active_cart_amount_query->where("branch_id", $branch_id);
        $active_cart_amount = floatval($active_cart_amount_query->sum("amount"));

        // In-progress orders (pending, confirmed, processing, out_for_delivery)
        $in_progress_query = $this->orders->query()
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery'])
            ->where(function($q) {
                $q->where('status', 1)->orWhereNull('status');
            });
        if ($only_online) $in_progress_query->where('pos', 0);
        if ($branch_id) $in_progress_query->where('branch_id', $branch_id);
        $in_progress_count = $in_progress_query->count();
        $in_progress_amount = floatval($in_progress_query->sum('amount'));

        $active_orders = $in_progress_count + $table_active_orders;
        $active_amount = number_format($in_progress_amount + $active_cart_amount, 2, '.', '');

        // 3. Cashiers
        $online_cashiers_query = CashierMan::whereHas("tokens");
        if ($branch_id) $online_cashiers_query->where("branch_id", $branch_id);
        $online_cashiers = $online_cashiers_query->count();

        $offline_cashiers_query = CashierMan::whereDoesntHave("tokens");
        if ($branch_id) $offline_cashiers_query->where("branch_id", $branch_id);
        $offline_cashiers = $offline_cashiers_query->count();

        // 4. Orders Summary for the selected period
        $baseOrdersQuery = function($withDates = false) use ($branch_id, $only_online, $start, $end) {
            $q = $this->orders->query();
            if ($only_online) $q->where('pos', 0);
            if ($branch_id) $q->where('branch_id', $branch_id);
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

        // 5. Hourly Series (Aligned to 24 hours of the operating period)
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
        ->where("created_at", ">=", $start)->where("created_at", "<=", $end);
        if ($only_online) $orders_hourly_raw->where('pos', 0);
        if ($branch_id) $orders_hourly_raw->where('branch_id', $branch_id);
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

        $expenses_hourly_query = Expense::selectRaw("DATE_FORMAT(created_at, '%h:00 %p') as hour, sum(amount) as total_amount")
            ->groupBy("hour")
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end);
        if ($branch_id) $expenses_hourly_query->where('branch_id', $branch_id);
        $expenses_hourly = $expenses_hourly_query->get();

        // 6. Top Products, Payment Methods, Financial Accounts, Branch Sales
        // ✅ Uses exact sales logic from product_report
        $top_product = $this->getProductSalesFromOrders($start, $end, $branch_id, $only_online, 5);
        if ($top_product->isEmpty() && !$request->filled('from') && !$request->filled('to')) {
            $top_product = $this->getProductSalesFromOrders(null, null, $branch_id, $only_online, 5);
        }

        $top_financial_query = OrderFinancial::selectRaw("financial_id, sum(amount) as total_amount")
            ->whereHas("order", function($query) use ($branch_id, $only_online, $start, $end){
                if ($only_online) $query->where('orders.pos', 0);
                if ($branch_id) $query->where("orders.branch_id", $branch_id);
                $query->where("orders.created_at", ">=", $start)->where("orders.created_at", "<=", $end);
            })
            ->groupBy("financial_id")
            ->with("financials:id,name") 
            ->orderByDesc("total_amount")
            ->limit(5);
        $top_financial = $top_financial_query->get();
        if ($top_financial->isEmpty() && !$request->filled('from') && !$request->filled('to')) {
            $top_financial = OrderFinancial::selectRaw("financial_id, sum(amount) as total_amount")
                ->whereHas("order", function($query) use ($branch_id, $only_online){
                    if ($only_online) $query->where('orders.pos', 0);
                    if ($branch_id) $query->where("orders.branch_id", $branch_id);
                })
                ->groupBy("financial_id")
                ->with("financials:id,name") 
                ->orderByDesc("total_amount")
                ->limit(5)
                ->get();
        }

        $top_payment_method_query = Order::selectRaw("payment_method_id, sum(amount) as total_amount")
            ->whereNotNull("payment_method_id")
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
        if ($branch_id) $top_payment_method_query->where("branch_id", $branch_id);
        $top_payment_method = $top_payment_method_query->get();
        if ($top_payment_method->isEmpty() && !$request->filled('from') && !$request->filled('to')) {
            $fb_pm = Order::selectRaw("payment_method_id, sum(amount) as total_amount")
                ->whereNotNull("payment_method_id")
                ->where(function($query){
                    $query->where("order_status", "!=", "returned")
                          ->where("order_status", "!=", "refund")
                          ->where("is_void", 0);
                })
                ->groupBy("payment_method_id")
                ->with("payment_method:id,name")
                ->orderByDesc("total_amount")
                ->limit(5);
            if ($only_online) $fb_pm->where('pos', 0);
            if ($branch_id) $fb_pm->where("branch_id", $branch_id);
            $top_payment_method = $fb_pm->get();
        }

        $branch_sales_query = Order::selectRaw("branch_id, sum(amount) as total_amount")
            ->where("created_at", ">=", $start)->where("created_at", "<=", $end)
            ->where(function($query){
                $query->where("order_status", "!=", "returned")
                      ->where("order_status", "!=", "refund")
                      ->where("is_void", 0);
            })
            ->groupBy("branch_id")
            ->with("branch:id,name")
            ->orderByDesc("total_amount");
        if ($only_online) $branch_sales_query->where('pos', 0);
        if ($branch_id) $branch_sales_query->where("branch_id", $branch_id);
        $branch_sales = $branch_sales_query->get();
        if ($branch_sales->isEmpty() && !$request->filled('from') && !$request->filled('to')) {
            $fb_bs = Order::selectRaw("branch_id, sum(amount) as total_amount")
                ->where(function($query){
                    $query->where("order_status", "!=", "returned")
                          ->where("order_status", "!=", "refund")
                          ->where("is_void", 0);
                })
                ->groupBy("branch_id")
                ->with("branch:id,name")
                ->orderByDesc("total_amount");
            if ($only_online) $fb_bs->where('pos', 0);
            if ($branch_id) $fb_bs->where("branch_id", $branch_id);
            $branch_sales = $fb_bs->get();
        }

        // 7. Monthly Statistics for the current year
        $currentYear = Carbon::now()->year;
        $monthly_orders_query = $this->orders->select('created_at', 'amount')
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
        if ($branch_id) $monthly_orders_query->where("branch_id", $branch_id);
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

        // 8. Recent Orders
        $recent_orders_query = $this->orders
            ->with(['branch:id,name', 'user:id,name,phone'])
            ->where(function($query) {
                $query->where('status', 1)->orWhereNull('status');
            })
            ->orderByDesc("created_at")
            ->limit(10);
        if ($only_online) $recent_orders_query->where('pos', 0);
        if ($branch_id) $recent_orders_query->where('branch_id', $branch_id);
        $recent_orders = $recent_orders_query->get();

        // 9. Top Selling Products (matches product_report) & Customers & Deals
        $top_selling = $this->getProductSalesFromOrders($start, $end, $branch_id, $only_online, 20);
        if ($top_selling->isEmpty() && !$request->filled('from') && !$request->filled('to')) {
            $top_selling = $this->getProductSalesFromOrders(null, null, $branch_id, $only_online, 20);
        }

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

        $top_customers_query = $this->users->query()
            ->withCount(['order as period_order_count' => function($query) use ($branch_id, $start, $end, $only_online) {
                if ($only_online) $query->where('orders.pos', 0);
                if ($branch_id) $query->where("orders.branch_id", $branch_id);
                $query->where("orders.created_at", ">=", $start)->where("orders.created_at", "<=", $end);
            }])
            ->withCount(['order as total_order_count' => function($query) use ($branch_id, $only_online) {
                if ($only_online) $query->where('orders.pos', 0);
                if ($branch_id) $query->where("orders.branch_id", $branch_id);
            }]);

        $users_list = $top_customers_query->get();
        $hasPeriodOrders = $users_list->sum('period_order_count') > 0;

        $top_customers = $users_list->map(function($u) use ($hasPeriodOrders) {
            $count = $hasPeriodOrders ? (int)$u->period_order_count : (int)$u->total_order_count;
            $u->orders_count = $count;
            $u->order_count = $count;
            return $u;
        })
        ->filter(function($u) {
            return ($u->orders_count ?? 0) > 0;
        })
        ->sortByDesc('orders_count')
        ->take(10)
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
            "hourly_timeline" => array_values($hours_24),
            "top_payment_method" => $top_payment_method,
            "branch_sales" => $branch_sales,
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
     * ✅ FIXED OLD API:
     * GET /admin/home/home_data
     */
    public function home_data(Request $request){
        $validator = Validator::make($request->all(), [
            'from' => 'date',
            'to' => 'date',
            'branch_id' => 'exists:branches,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $branch_id = $request->branch_id;
        list($start, $end) = $this->getOperatingDateRange($request, $branch_id);

        $from = $request->filled('from') ? $start->format('Y-m-d H:i:s') : null;
        $to = $request->filled('to') ? $end->format('Y-m-d H:i:s') : null;

        $only_online = $request->boolean('only_online') || $request->boolean('is_online') || $request->pos === '0' || $request->pos === 0;

        $occupied_tables_query = CafeTable::whereIn("current_status", [
            "not_available_pre_order", 
            "not_available_with_order", 
            "not_available_but_checkout"
        ]);
        if($branch_id){
            $occupied_tables_query->where("branch_id", $branch_id);
        }
        $occupied_tables = $occupied_tables_query->count();

        $table_active_orders_query = CafeTable::whereIn("current_status", ["not_available_with_order"]);
        if($branch_id){
            $table_active_orders_query->where("branch_id", $branch_id);
        }
        $table_active_orders = $table_active_orders_query->count();

        $tables_ids_query = CafeTable::whereIn("current_status", ["not_available_pre_order", "not_available_with_order"]);
        if($branch_id){
            $tables_ids_query->where("branch_id", $branch_id);
        }
        $tables_ids = $tables_ids_query->pluck("id")->toArray();

        $active_cart_amount_query = OrderCart::whereIn("table_id", $tables_ids);
        if($branch_id){
            $active_cart_amount_query->where("branch_id", $branch_id);
        }
        $active_cart_amount = floatval($active_cart_amount_query->sum("amount"));

        // Count in-progress orders
        $in_progress_query = $this->orders->query()
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery'])
            ->where(function($q) {
                $q->where('status', 1)->orWhereNull('status');
            });
        if ($only_online) $in_progress_query->where('pos', 0);
        if ($branch_id) $in_progress_query->where('branch_id', $branch_id);
        $in_progress_count = $in_progress_query->count();
        $in_progress_amount = floatval($in_progress_query->sum('amount'));

        $active_orders = $in_progress_count + $table_active_orders;
        $active_amount = number_format($in_progress_amount + $active_cart_amount, 2, '.', '');

        $online_cashiers_query = CashierMan::whereHas("tokens");
        if($branch_id){
            $online_cashiers_query->where("branch_id", $branch_id);
        }
        $online_cashiers = $online_cashiers_query->count();

        $offline_cashiers_query = CashierMan::whereDoesntHave("tokens");
        if($branch_id){
            $offline_cashiers_query->where("branch_id", $branch_id);
        }
        $offline_cashiers = $offline_cashiers_query->count();

        // ✅ Uses exact sales logic from product_report
        $top_product = $this->getProductSalesFromOrders($start, $end, $branch_id, $only_online, 5);

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

        $payments_hourly = Order::
        selectRaw("DATE_FORMAT(created_at, '%Y-%m-%d %H %p') as hour, sum(amount) as total_amount")
        ->groupBy("hour") 
        ->where("created_at", ">=", $start)
        ->where("created_at", "<=", $end) 
        ->where(function($query){
            $query->where("order_status", "!=", "returned")
            ->where("order_status", "!=", "refund")
            ->where("is_void", 0);
        })
        ->where(function($query){
            $query->where('payment_status', 'paid')
                  ->orWhereNotNull('payment_method_id');
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
        ->where("created_at", "<=", $end);

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

        if ($only_online) {
            $order_types->where('pos', 0);
            $sales_hourly->where('pos', 0);
            $payments_hourly->where('pos', 0);
            $return_hourly->where('pos', 0);
            $discount_hourly->where('pos', 0);
            $top_payment_method->where('pos', 0);
            $branch_sales->where('pos', 0);
        }

        if($branch_id){ 
            $top_financial
            ->whereHas("order", function($query) use($branch_id, $only_online){
                if ($only_online) $query->where('pos', 0);
                $query->where("branch_id", $branch_id);
            });
            $top_payment_method->where("branch_id", $branch_id);
            $order_types->where("branch_id", $branch_id);
            $sales_hourly->where("branch_id", $branch_id);
            $payments_hourly->where("branch_id", $branch_id);
            $return_hourly->where("branch_id", $branch_id);
            $discount_hourly->where("branch_id", $branch_id);
            $branch_sales->where("branch_id", $branch_id);
            $expenses_hourly->where("branch_id", $branch_id);
        }

        // Correct date filters (>= from and <= to)
        if($from){
            $top_financial->whereHas("order", function($q) use ($from) { $q->where("created_at", ">=", $from); });
            $top_payment_method->where("created_at", ">=", $from);
            $branch_sales->where("created_at", ">=", $from);
        }
        if($to){
            $top_financial->whereHas("order", function($q) use ($to) { $q->where("created_at", "<=", $to); });
            $top_payment_method->where("created_at", "<=", $to);
            $branch_sales->where("created_at", "<=", $to);
        }

        $top_financial = $top_financial->get();
        $top_payment_method = $top_payment_method->get();
        $order_types = $order_types->get();
        $sales_hourly = $sales_hourly->get();
        $payments_hourly = $payments_hourly->get();
        $return_hourly = $return_hourly->get();
        $discount_hourly = $discount_hourly->get();
        $branch_sales = $branch_sales->get();
        $expenses_hourly = $expenses_hourly->get();
 
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
            "branch_sales" => $branch_sales,
            "expenses_hourly" => $expenses_hourly,
        ]);
    }

    public function notifications_count(Request $request){
        $query = Notification::where("is_read", false);
        if($request->user()->role == "branch"){
            $branchId = (int)$request->user()->id;
            $query->where(function($q) use ($branchId) {
                $q->whereJsonContains("branch_ids", $branchId)
                  ->orWhereJsonContains("branch_ids", (string)$branchId)
                  ->orWhereNull("branch_ids")
                  ->orWhere('branch_ids', '[]')
                  ->orWhere('branch_ids', '');
            });
        }
        $count = $query->count();
        return response()->json([
            "count" => $count,
            "notifications_count" => $count
        ]);
    }

    public function notifications(Request $request){
        $query = Notification::orderByDesc("created_at");
        if($request->user()->role == "branch"){
            $branchId = (int)$request->user()->id;
            $query->where(function($q) use ($branchId) {
                $q->whereJsonContains("branch_ids", $branchId)
                  ->orWhereJsonContains("branch_ids", (string)$branchId)
                  ->orWhereNull("branch_ids")
                  ->orWhere('branch_ids', '[]')
                  ->orWhere('branch_ids', '');
            });
        }
        $notifications = $query->paginate(10);
        return response()->json([
            "notifications" => $notifications
        ]);
    }

    public function mark_notification_read(Request $request){
        $id = $request->input('id');
        if($id){
            Notification::where('id', $id)->update(['is_read' => true]);
        } else {
            $query = Notification::where('is_read', false);
            if($request->user()->role == "branch"){
                $branchId = (int)$request->user()->id;
                $query->where(function($q) use ($branchId) {
                    $q->whereJsonContains("branch_ids", $branchId)
                      ->orWhereJsonContains("branch_ids", (string)$branchId)
                      ->orWhereNull("branch_ids")
                      ->orWhere('branch_ids', '[]')
                      ->orWhere('branch_ids', '');
                });
            }
            $query->update(['is_read' => true]);
        }
        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read'
        ]);
    }

    /**
     * ✅ FIXED OLD API:
     * GET /admin/home/orders
     * Counts all orders by default (POS + Online), unless only_online is explicitly requested.
     */
    public function home_orders_count(Request $request = null){ 
        if (!$request) {
            $request = request();
        }

        $msg_package = $this->getSmsPackageInfo();

        $only_online = $request->boolean('only_online') || $request->boolean('is_online') || $request->pos === '0' || $request->pos === 0;
        $branch_id = $request->branch_id;

        $baseOrderQuery = function() use ($branch_id, $only_online, $request) {
            $q = $this->orders->query();
            // ✅ Only filter pos=0 if explicitly requested as online-only
            if ($only_online) {
                $q->where('pos', 0);
            }
            if ($branch_id) {
                $q->where('branch_id', $branch_id);
            }
            if ($request->filled('from')) {
                $q->where('created_at', '>=', $request->from . ' 00:00:00');
            }
            if ($request->filled('to')) {
                $q->where('created_at', '<=', $request->to . ' 23:59:59');
            }
            return $q->where(function($query) {
                $query->where('status', 1)
                      ->orWhereNull('status');
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
     * GET /admin/home
     */
    public function home(Request $request){
        $validator = Validator::make($request->all(), [
            'from' => 'date',
            'to' => 'date',
            'branch_id' => 'exists:branches,id',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $branch_id = $request->branch_id;
        list($start, $end) = $this->getOperatingDateRange($request, $branch_id);

        $from = $request->filled('from') ? $start->format('Y-m-d H:i:s') : null;
        $to = $request->filled('to') ? $end->format('Y-m-d H:i:s') : null;
        $only_online = $request->boolean('only_online') || $request->boolean('is_online') || $request->pos === '0' || $request->pos === 0;
        
        $msg_package = $this->getSmsPackageInfo();
 
        $this->log_order
        ->whereDate('created_at', '<=', now()->subDays(14))
        ->delete();

        $currentYear = Carbon::now()->year; 
        $all_orders_query = $this->orders
        ->select('created_at', 'amount')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc("created_at");

        // ✅ Only filter pos=0 if explicitly requested as online-only
        if ($only_online) {
            $all_orders_query->where('pos', 0);
        }
        if($branch_id){
            $all_orders_query->where("branch_id", $branch_id);
        }
        // ✅ Correct date filters
        if($from){
            $all_orders_query->where("created_at", ">=", $from);
        }
        if($to){
            $all_orders_query->where("created_at", "<=", $to);
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

        // ✅ Uses exact sales logic from product_report
        $top_selling = $this->getProductSalesFromOrders($from ? $start : null, $to ? $end : null, $branch_id, $only_online, 20);
       
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

        $users = $this->users
        ->withCount(['order' => function($query) use ($branch_id, $from, $to, $only_online) {
            if ($only_online) {
                $query->where('orders.pos', 0);
            }
            if ($branch_id) {
                $query->where("orders.branch_id", $branch_id);
            }
            if ($from) {
                $query->where("orders.created_at", ">=", $from); 
            }
            if ($to) {
                $query->where("orders.created_at", "<=", $to);
            }
        }]) 
        ->get();
        $top_customers = $users
        ->sortByDesc('order_count')->values();

        $recent_orders_query = $this->orders
        ->with(['branch:id,name', 'user:id,name,phone'])
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->orderByDesc("created_at")
        ->limit(10);

        if ($only_online) {
            $recent_orders_query->where('pos', 0);
        }
        if ($branch_id) {
            $recent_orders_query->where('branch_id', $branch_id);
        }
        $recent_orders = $recent_orders_query->get();

        return response()->json([ 
            'order_statistics' => $order_statistics,
            'earning_statistics' => $earning_statistics,
            'recent_orders' => $recent_orders,
            'top_selling' => $top_selling,
            'offers' => $deals,
            'top_customers' => $top_customers,
            'msg_package' => $msg_package,
        ]);
    }
}
