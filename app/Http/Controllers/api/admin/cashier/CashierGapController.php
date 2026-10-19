<?php

namespace App\Http\Controllers\api\admin\cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\CashierGap;
use App\Models\Cashier;
use App\Models\CashierMan;
use App\Models\CashierShift;
use App\Models\Order;
use App\Models\OrderFinancial;
use App\Models\FinantiolAcounting;
use App\Models\Expense;
use App\Models\TablePeople;
use Carbon\Carbon;

class CashierGapController extends Controller
{
    public function lists(Request $request){
        $cashiers = Cashier::
        select("id", "name")
        ->get();
        $cashier_men = CashierMan::
        select("id", "user_name")
        ->get();

        return response()->json([
            "cashiers" => $cashiers,
            "cashier_men" => $cashier_men,
        ]);
    }

    public function cashier_gap(Request $request){
        $validation = Validator::make($request->all(), [
            'cashier_id' => 'exists:cashiers,id',
            'cashier_man_id' => 'exists:cashier_men,id',
        ]);
        if ($validation->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validation->errors(),
            ],400);
        }
        $gaps = CashierGap::
        with("cashier", "cashier_man", "shift");
        if($request->cashier_id){
            $gaps = $gaps
            ->where("cashier_id", $request->cashier_id);
        }
        if($request->cashier_man_id){
            $gaps = $gaps
            ->where("cashier_man_id", $request->cashier_man_id);
        }

        $perPage = $request->per_page ?? 20;

        $paginated = $gaps
        ->orderByDesc("created_at")
        ->paginate($perPage);

        $paginated->getCollection()->transform(function($item){
            $date = $item?->shift?->created_at ?? $item->created_at;
            return [
                "id" => $item->id,
                "amount" => $item->amount,
                "cashier_amount" => $item->cashier_amount,
                "cashier_id" => $item->cashier_id,
                "cashier_man_id" => $item->cashier_man_id,
                "cashier" => $item?->cashier?->name,
                "cashier_man" => $item?->cashier_man?->user_name,
                "date" => $date ? (is_string($date) ? Carbon::parse($date)->format("Y-m-d") : $date->format("Y-m-d")) : null,
            ];
        });

        return response()->json([
            "gaps" => $paginated->items(),
            "pagination" => [
                "total" => $paginated->total(),
                "per_page" => $paginated->perPage(),
                "current_page" => $paginated->currentPage(),
                "last_page" => $paginated->lastPage(),
            ]
        ]);
    }

    public function end_shift_report(Request $request, $gap_id)
    {
        // Load the gap — try exact ID first
        $gap = CashierGap::with(['cashier_man', 'cashier', 'shift'])->find($gap_id);

        // Fallback 1: If JS rounded the last digits of a 15+ digit snowflake ID (e.g. 17914503770368580)
        if (!$gap && strlen((string)$gap_id) >= 14) {
            $prefix = substr((string)$gap_id, 0, 14);
            $query = CashierGap::with(['cashier_man', 'cashier', 'shift'])
                ->where('id', 'LIKE', $prefix . '%');
            if ($request->cashier_man_id) {
                $query->where('cashier_man_id', $request->cashier_man_id);
            }
            if ($request->cashier_amount !== null) {
                $query->where('cashier_amount', $request->cashier_amount);
            }
            $gap = $query->first();
        }

        // Fallback 2: Look up by cashier_man_id and gap parameters
        if (!$gap && $request->cashier_man_id) {
            $gap = CashierGap::with(['cashier_man', 'cashier', 'shift'])
                ->where('cashier_man_id', $request->cashier_man_id)
                ->when($request->cashier_amount !== null, fn($q) => $q->where('cashier_amount', $request->cashier_amount))
                ->when($request->amount !== null, fn($q) => $q->where('amount', $request->amount))
                ->latest()
                ->first();
        }

        if (!$gap) {
            return response()->json(['errors' => 'Gap not found'], 404);
        }

        $cashier_man_id = $gap->cashier_man_id;
        $cashier_amount = $gap->cashier_amount ?? 0;

        // ── Resolve the shift and shift_number ───────────────────────────────────
        $rawShift = $gap->getRawOriginal('shift');
        $shiftRecord = null;

        if ($rawShift) {
            $shiftRecord = CashierShift::where('shift', $rawShift)
                ->where('cashier_man_id', $cashier_man_id)
                ->with('financial')
                ->first();
            if (!$shiftRecord) {
                $shiftRecord = CashierShift::where('shift', $rawShift)->with('financial')->first();
            }
        }

        // If not found by raw shift number, find the CashierShift active at gap creation
        if (!$shiftRecord) {
            $gapCreatedAt = $gap->created_at ?? now();

            // 1. Shift created on or before gap created_at
            $shiftRecord = CashierShift::where('cashier_man_id', $cashier_man_id)
                ->when($gap->cashier_id, fn($q) => $q->where('cashier_id', $gap->cashier_id))
                ->where('created_at', '<=', $gapCreatedAt)
                ->orderByDesc('created_at')
                ->with('financial')
                ->first();

            // 2. Or shift on the same date
            if (!$shiftRecord) {
                $gapDate = $gap->created_at ? $gap->created_at->format('Y-m-d') : ($request->date ?? date('Y-m-d'));
                $shiftRecord = CashierShift::where('cashier_man_id', $cashier_man_id)
                    ->whereDate('created_at', $gapDate)
                    ->orderByDesc('created_at')
                    ->with('financial')
                    ->first();
            }

            // 3. Or most recent shift for this cashier_man
            if (!$shiftRecord) {
                $shiftRecord = CashierShift::where('cashier_man_id', $cashier_man_id)
                    ->orderByDesc('created_at')
                    ->with('financial')
                    ->first();
            }
        }

        $shift = $shiftRecord;
        $shift_number = $shift?->shift ?? $rawShift;

        // Fallback: If still no shift number, find shift from orders by this cashier_man around this time
        if (!$shift_number) {
            $gapDate = $gap->created_at ? $gap->created_at->format('Y-m-d') : ($request->date ?? date('Y-m-d'));
            $shift_number = Order::where('cashier_man_id', $cashier_man_id)
                ->whereNotNull('shift')
                ->whereDate('created_at', $gapDate)
                ->orderByDesc('created_at')
                ->value('shift');

            if ($shift_number && !$shift) {
                $shift = CashierShift::where('shift', $shift_number)->with('financial')->first();
            }
        }

        // Safety: If shift_number is STILL null, use an impossible ID so it NEVER matches shift IS NULL
        $safeShiftNumber = $shift_number ?? -999999;

        // ── order totals ────────────────────────────────────────────────────────
        $baseQuery = function () use ($cashier_man_id, $safeShiftNumber) {
            return Order::where('cashier_man_id', $cashier_man_id)
                ->where('shift', $safeShiftNumber)
                ->where('is_void', 0)
                ->where('due', 0)
                ->where('due_module', 0)
                ->where(function ($q) { $q->where('status', 1)->orWhereNull('status'); })
                ->where(function ($q) {
                    $q->where('due_from_delivery', 0)->where('order_type', 'delivery')
                      ->orWhere('due_from_delivery', 1)->where('order_type', '!=', 'delivery');
                })
                ->where(function ($q) {
                    $q->where('pos', 1)->orWhere('pos', 0)->where('order_status', '!=', 'pending');
                })
                ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled']);
        };

        $total_orders   = (clone $baseQuery())->sum('amount');
        $total_discount = (clone $baseQuery())->sum('total_discount');
        $total_tax      = (clone $baseQuery())->sum('total_tax');

        $principle_price     = $total_orders + $total_discount - $total_tax;
        $price_after_discount = $total_orders - $total_tax;

        // ── expenses ────────────────────────────────────────────────────────────
        $start_time = $shift->start_time ?? now();
        $end_time   = $shift->end_time   ?? now();

        $expenses_sum = Expense::where('created_at', '>=', $start_time)
            ->where('created_at', '<=', $end_time)
            ->where('cahier_man_id', $cashier_man_id)
            ->sum('amount');

        $start_amount   = $shift->amount ?? 0;
        $actual_total   = $total_orders + $start_amount - $expenses_sum;
        $net_cash_drawer = $actual_total;

        // Main financial account (cash)
        $financial = FinantiolAcounting::where('main', 1)
            ->whereHas('branch', function ($q) use ($cashier_man_id) {
                $cashier_man = CashierMan::find($cashier_man_id);
                $q->where('branches.id', $cashier_man->branch_id ?? 0);
            })->first();

        $order_financial = OrderFinancial::where('financial_id', $financial->id ?? 0)
            ->whereHas('order', function ($q) use ($cashier_man_id, $safeShiftNumber) {
                $q->where('cashier_man_id', $cashier_man_id)
                  ->where('shift', $safeShiftNumber)
                  ->where('is_void', 0)
                  ->where('due', 0)
                  ->where('due_module', 0);
            })->sum('amount');

        $cash_expenses = Expense::where('financial_account_id', $financial->id ?? 0)
            ->where('created_at', '>=', $start_time)
            ->where('created_at', '<=', $end_time)
            ->where('cahier_man_id', $cashier_man_id)
            ->sum('amount');

        $net_cash_drawer2 = $order_financial + $start_amount - $cash_expenses;

        // Gap
        $gap_amount = $order_financial - $cash_expenses - $cashier_amount;

        // ── order counts / amounts by type ──────────────────────────────────────
        $orders_count = [
            'dine_in_orders_count'   => Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('order_type', 'dine_in')->where('is_void', 0)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->where(function($q){$q->where('status',1)->orWhereNull('status');})->count(),
            'take_away_orders_count' => Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('order_type', 'take_away')->where('is_void', 0)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->where(function($q){$q->where('status',1)->orWhereNull('status');})->count(),
            'delivery_orders_count'  => Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('order_type', 'delivery')->where('is_void', 0)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->where(function($q){$q->where('status',1)->orWhereNull('status');})->count(),
        ];
        $orders_amount = [
            'dine_in_amount'   => Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('order_type', 'dine_in')->where('is_void', 0)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->where(function($q){$q->where('status',1)->orWhereNull('status');})->sum('amount'),
            'take_away_amount' => Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('order_type', 'take_away')->where('is_void', 0)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->where(function($q){$q->where('status',1)->orWhereNull('status');})->sum('amount'),
            'delivery_amount'  => Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('order_type', 'delivery')->where('is_void', 0)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->where(function($q){$q->where('status',1)->orWhereNull('status');})->sum('amount'),
        ];

        // ── void orders ─────────────────────────────────────────────────────────
        $order_count      = Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->count();
        $void_order_count = Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('is_void', 1)->count();
        $void_order_sum   = Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('is_void', 1)->sum('amount');

        // ── due ─────────────────────────────────────────────────────────────────
        $due_module = Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('is_void', 0)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->where('due_module', '>', 0)->sum('due_module');
        $due_user   = Order::where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('is_void', 0)->where('due', 1)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->sum('amount');

        // ── order IDs by type ───────────────────────────────────────────────────
        $take_away_orders = Order::select('id')->where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('order_type', 'take_away')->where('is_void', 0)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->pluck('id')->toArray();
        $delivery_orders  = Order::select('id')->where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('order_type', 'delivery')->where('due_from_delivery', 0)->where('is_void', 0)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->pluck('id')->toArray();
        $dine_in_orders   = Order::select('id')->where('cashier_man_id', $cashier_man_id)->where('shift', $safeShiftNumber)->where('order_type', 'dine_in')->where('is_void', 0)->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])->pluck('id')->toArray();

        // ── financial accounts breakdown ─────────────────────────────────────────
        $delivery_fa  = OrderFinancial::selectRaw('financial_id, SUM(amount) as total_amount')->whereIn('order_id', $delivery_orders)->with('financials')->groupBy('financial_id')->get();
        $takeaway_fa  = OrderFinancial::selectRaw('financial_id, SUM(amount) as total_amount')->whereIn('order_id', $take_away_orders)->with('financials')->groupBy('financial_id')->get();
        $dinein_fa    = OrderFinancial::selectRaw('financial_id, SUM(amount) as total_amount')->whereIn('order_id', $dine_in_orders)->with('financials')->groupBy('financial_id')->get();

        $financial_accounts = [];
        $total_amount_fa = 0;

        foreach ($delivery_fa as $item) {
            $total_amount_fa += $item->total_amount;
            $financial_accounts[$item->financial_id] = [
                'financial_id'         => $item->financial_id,
                'financial_name'       => $item?->financials?->name,
                'total_amount_delivery'  => $item->total_amount,
                'total_amount_take_away' => 0,
                'total_amount_dine_in'   => 0,
            ];
        }
        foreach ($takeaway_fa as $item) {
            $total_amount_fa += $item->total_amount;
            if (isset($financial_accounts[$item->financial_id])) {
                $financial_accounts[$item->financial_id]['total_amount_take_away'] += $item->total_amount;
            } else {
                $financial_accounts[$item->financial_id] = [
                    'financial_id'         => $item->financial_id,
                    'financial_name'       => $item?->financials?->name,
                    'total_amount_delivery'  => 0,
                    'total_amount_take_away' => $item->total_amount,
                    'total_amount_dine_in'   => 0,
                ];
            }
        }
        foreach ($dinein_fa as $item) {
            $total_amount_fa += $item->total_amount;
            if (isset($financial_accounts[$item->financial_id])) {
                $financial_accounts[$item->financial_id]['total_amount_dine_in'] += $item->total_amount;
            } else {
                $financial_accounts[$item->financial_id] = [
                    'financial_id'         => $item->financial_id,
                    'financial_name'       => $item?->financials?->name,
                    'total_amount_delivery'  => 0,
                    'total_amount_take_away' => 0,
                    'total_amount_dine_in'   => $item->total_amount,
                ];
            }
        }

        // Subtract expenses from financial accounts
        $expenses_detail = Expense::where('created_at', '>=', $start_time)
            ->where('created_at', '<=', $end_time)
            ->where('cahier_man_id', $cashier_man_id)
            ->with('financial_account')
            ->get();

        $expenses_total = 0;
        foreach ($expenses_detail as $item) {
            $expenses_total += $item->amount;
            $total_amount_fa -= $item->amount;
            if (isset($financial_accounts[$item->financial_account_id])) {
                $financial_accounts[$item->financial_account_id]['total_amount_delivery'] -= $item->amount;
            } else {
                $financial_accounts[$item->financial_account_id] = [
                    'financial_id'         => $item->financial_account_id,
                    'financial_name'       => $item?->financial_account?->name,
                    'total_amount_delivery'  => -$item->amount,
                    'total_amount_take_away' => 0,
                    'total_amount_dine_in'   => 0,
                ];
            }
        }

        $financial_accounts = collect($financial_accounts)->values();

        // Expenses grouped by financial account + category
        $expenses_grouped = Expense::selectRaw('financial_account_id, category_id, SUM(amount) AS total')
            ->where('created_at', '>=', $start_time)
            ->where('created_at', '<=', $end_time)
            ->where('cahier_man_id', $cashier_man_id)
            ->with('financial_account', 'category')
            ->groupBy('financial_account_id')
            ->groupBy('category_id')
            ->get()
            ->map(function ($item) {
                return [
                    'financial_account' => $item?->financial_account?->name,
                    'category'          => $item?->category?->name,
                    'total'             => $item->total,
                ];
            });

        // ── online orders ────────────────────────────────────────────────────────
        $online_order_paid = Order::selectRaw('payment_method_id, SUM(amount) AS amount')
            ->where('pos', 0)->where('is_void', 0)
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])
            ->where(function ($q) {
                $q->where('payment_method_id', '!=', 2)->where(function ($q2) { $q2->where('status', 1)->orWhereNull('status'); })
                  ->orWhereHas('financial_accountigs');
            })
            ->where('shift', $safeShiftNumber)
            ->with('payment_method')->groupBy('payment_method_id')->groupBy('order_type')->get()
            ->map(fn($i) => ['payment_method' => $i?->payment_method?->name, 'payment_method_id' => $i->payment_method_id, 'amount' => $i->amount]);

        $online_order_unpaid = Order::selectRaw('payment_method_id, SUM(amount) AS amount')
            ->where('pos', 0)->where('payment_method_id', 2)->whereDoesntHave('financial_accountigs')
            ->where(function ($q) { $q->where('status', 1)->orWhereNull('status'); })
            ->where('is_void', 0)
            ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'delivered', 'scheduled'])
            ->where('shift', $safeShiftNumber)
            ->with('payment_method')->groupBy('payment_method_id')->groupBy('order_type')->get()
            ->map(fn($i) => ['payment_method' => $i?->payment_method?->name, 'payment_method_id' => $i->payment_method_id, 'amount' => $i->amount]);

        $paid_map = [];
        foreach ($online_order_paid as $item) {
            if (isset($paid_map[$item['payment_method_id']])) $paid_map[$item['payment_method_id']]['amount'] += $item['amount'];
            else $paid_map[$item['payment_method_id']] = $item;
        }
        $unpaid_map = [];
        foreach ($online_order_unpaid as $item) {
            if (isset($unpaid_map[$item['payment_method_id']])) $unpaid_map[$item['payment_method_id']]['amount'] += $item['amount'];
            else $unpaid_map[$item['payment_method_id']] = $item;
        }
        $online_order = ['paid' => array_values($paid_map), 'un_paid' => array_values($unpaid_map)];

        // ── table people ─────────────────────────────────────────────────────────
        $people = TablePeople::where('is_active', 0)->where('shift_number', $safeShiftNumber)->sum('count') ?? 0;

        $start_balance = [
            'amount'    => $shift->amount ?? 0,
            'financial' => $shift?->financial?->name,
        ];

        return response()->json([
            'principle_price'    => $principle_price,
            'price_after_discount' => $price_after_discount,
            'actual_total'       => $actual_total,
            'start_amount'       => $start_amount,
            'expenses'           => $expenses_grouped,
            'perimission'        => true,
            'financial_accounts' => $financial_accounts,
            'order_count'        => $order_count,
            'total_amount'       => $total_amount_fa,
            'expenses_total'     => $expenses_total,
            'total_orders'       => $total_orders,
            'net_cash_drawer'    => $net_cash_drawer,
            'net_cash_drawer2'   => $net_cash_drawer2,
            'online_order'       => $online_order,
            'void_order_count'   => $void_order_count,
            'void_order_sum'     => $void_order_sum,
            'due_module'         => $due_module,
            'due_user'           => $due_user,
            'start_balance'      => $start_balance,
            'orders_count'       => $orders_count,
            'orders_amount'      => $orders_amount,
            'table_people'       => $people,
            'gap'                => $gap_amount,
            'cashier_amount'     => $cashier_amount,
            // meta from gap
            'cashier_man'        => $gap?->cashier_man?->user_name,
            'cashier'            => $gap?->cashier?->name,
            'shift_number'       => $shift_number,
            'shift_start'        => $shift?->start_time,
            'shift_end'          => $shift?->end_time,
        ]);
    }
}
