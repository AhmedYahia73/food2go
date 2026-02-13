<?php

namespace App\Http\Controllers\api\admin\shifts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
  
use App\Models\Order;
use App\Models\CafeLocation;
use App\Models\FinantiolAcounting;
use App\Models\OrderFinancial;
use App\Models\CashierShift;
use App\Models\CashierGap;
use App\Models\Expense;

class ShiftPanelController extends Controller
{

    public function shifts(Request $request){
        $cashier_shifts = CashierShift::
        whereNull("end_time")
        ->with("cashier_man", "cashier")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "start_time" => $item->start_time,
                "cashier_man" => $item?->cashier_man?->user_name,
                "cashier" => $item?->cashier?->name
            ];
        });

        return response()->json([
            "cashier_shifts" => $cashier_shifts
        ]);
    }


    public function end_shift(Request $request, $id){
        //  
        $cashier_shifts = CashierShift::
        where("id", $id)
        ->with("cashier_man", "cashier", 'financial') 
        ->first();
        if(!$cashier_shifts){
            return response()->json([
                "errors" => "id is wrong"
            ]);
        }
        $gap = 0;

        $total_orders = Order::
        select("id")
        ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
        ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
        ->where("is_void", 0) 
        ->where("due", 0)
        ->where("due_module", 0)
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where(function($query) {
            $query->where('due_from_delivery', 0)
            ->where('order_type', "delivery")
            ->orwhere('due_from_delivery', 1)
            ->where('order_type', "!=", "delivery");
        }) 
        ->where(function($query){
            $query->where('pos', 1)
            ->orWhere('pos', 0)
            ->where('order_status', '!=', 'pending');
        }) 
        ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
        ->sum('amount');
        
        $shift = Expense::
        where('created_at', '>=', $shift->start_time ?? now())
        ->where('created_at', '<=', $shift->end_time ?? now())
        ->where("branch_id", $cashier_shifts?->cashier_man?->branch_id)
        ->where("cahier_man_id", $cashier_shifts?->cashier_man?->id)
        ->first();
        $expenses = Expense::
        where('created_at', '>=', $shift->start_time ?? now())
        ->where('created_at', '<=', $shift->end_time ?? now())
        ->where("branch_id", $cashier_shifts?->cashier_man?->branch_id)
        ->where("cahier_man_id", $cashier_shifts?->cashier_man?->id)
        ->sum('amount');
        $start_amount = $shift->amount ?? 0; 
        $expenses = $expenses;
        $financial = FinantiolAcounting::
        where("main", 1)
        ->whereHas('branch', function($query) use($cashier_shifts){
            return $query->where("branches.id", $cashier_shifts?->cashier_man?->branch_id);
        })
        ->first();    
        $order_financial = OrderFinancial::
        where("financial_id", $financial->id ?? 0)
        ->whereHas("order", function($query) use($cashier_shifts){
            $query
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id)
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number)
            ->where("is_void", 0) 
            ->where("due", 0)
            ->where("due_module", 0);
        })
        ->sum("amount");
        $expenses = Expense::
        where("financial_account_id", $financial->id ?? 0)
        ->where('created_at', '>=', $shift->start_time ?? now())
        ->where('created_at', '<=', $shift->end_time ?? now())
        ->where("branch_id", $cashier_shifts?->cashier_man?->branch_id)
        ->where("cahier_man_id", $cashier_shifts?->cashier_man?->id)
        ->sum('amount');
        $net_cash_drawer = $order_financial + $start_amount - $expenses;
        $actual_total = $total_orders + $start_amount - $expenses; 
        if($cashier_shifts?->cashier_man?->hall_orders ?? 0){
            $hall_orders = CafeLocation::query()
            ->selectRaw("
                cafe_locations.id as hall_id,
                cafe_locations.name as hall_name,
                COUNT(orders.id) as order_count
            ")
            ->leftJoin('cafe_tables', 'cafe_tables.location_id', '=', 'cafe_locations.id')
            ->leftJoin('orders', function ($join) use ($request) {
                $join->on('orders.table_id', '=', 'cafe_tables.id')
                    ->where('orders.branch_id', $cashier_shifts?->cashier_man?->branch_id ?? 0)
                    ->where('orders.cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
                    ->where('orders.is_void', 0)
                    ->where('orders.shift', $cashier_shifts?->cashier_man?->shift_number ?? 0);
            })
            ->groupBy('cafe_locations.id', 'cafe_locations.name')
            ->get();
        }
        if (($cashier_shifts?->cashier_man?->report ?? 0 == "unactive" ) ||
        $cashier_shifts?->cashier_man?->enter_amount ?? 0 ) {
            $validator = Validator::make($request->all(), [
                'amount' => ['required', 'numeric'], 
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'errors' => $validator->errors(),
                ],400);
            }
            $main_financial_id = FinantiolAcounting::
            where("main", 1)
            ->whereHas('branch', function($query) use($cashier_shifts){
                return $query->where("branches.id", $cashier_shifts?->cashier_man?->branch_id);
            })
            ->first()?->id ?? 0;
            
            $orders_ids = Order::
            select("id")
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->pluck('id')
            ->toArray();
            
            $total_financial_accounts = OrderFinancial::
            selectRaw("financial_id ,SUM(amount) as total_amount")
            ->whereIn("order_id", $orders_ids)
            ->where("financial_id", $main_financial_id) 
            ->sum("amount");
            $cash_expenses = Expense::
            where('created_at', '>=', $shift->start_time ?? now())
            ->where('created_at', '<=', $shift->end_time ?? now())
            ->where("financial_account_id", $main_financial_id)
            ->where("branch_id", $cashier_shifts?->cashier_man?->branch_id)
            ->where("cahier_man_id", $cashier_shifts?->cashier_man?->id)
            ->sum('amount');
            $gap = $total_financial_accounts - $cash_expenses - $request->amount; 
            $shift = CashierShift::
            where("cashier_man_id", $cashier_shifts?->cashier_man?->id ?? 0)
            ->where("cashier_id", $cashier_shifts?->cashier_man?->cashier_id ?? 0)
            ->orderByDesc("id")
            ->first()?->shift ?? null;
            CashierGap::create([
                'cashier_id' => $cashier_shifts?->cashier_man?->cashier_id ?? 0,
                'cashier_man_id' => $cashier_shifts?->cashier_man?->id ?? 0,
                'amount' => $request->amount,
                'shift' => $cashier_shifts?->cashier_man?->shift_number ?? 0,
            ]);  
        }   
        if (($cashier_shifts?->cashier_man?->report ?? 0) == "unactive") {
            $arr = [
                "start_amount" => $start_amount,
                "expenses" => $expenses, 
                "total_orders" => $total_orders, 
                "actual_total" => $actual_total,
                "gap" => $gap,
                "net_cash_drawer" => $net_cash_drawer,
            ];
            if(isset($hall_orders)){
                $arr['hall_orders'] = $hall_orders;
            }
            $cashier_shifts->end_time = now();
            $cashier_shifts->save();
            return response()->json($arr);
        }
        if(($cashier_shifts?->cashier_man?->report ?? 0) != "unactive"){
            $order_count = Order::
            select("id")
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->count();
            
            $void_order_count = Order::  
            where("is_void", 1)     
            ->where("branch_id", $cashier_shifts?->cashier_man?->branch_id ?? 0)
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->count();
            $void_order_sum = Order::  
            where("is_void", 1)    
            ->where("branch_id", $cashier_shifts?->cashier_man?->branch_id ?? 0)
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->sum("amount");

            $take_away_orders = Order::
            select("id")
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->where("order_type", "take_away") 
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->pluck('id')
            ->toArray();
            $delivery_orders = Order::
            select("id")
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->where("order_type", "delivery")
            ->where("due_from_delivery", 0)
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->pluck('id')
            ->toArray();
            $dine_in_orders = Order::
            select("id")
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->where("order_type", "dine_in")
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->pluck('id')
            ->toArray();
            
            $shift = CashierShift::
            where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
            ->first();
            $expenses = Expense::
            where('created_at', '>=', $shift->start_time ?? now())
            ->where('created_at', '<=', $shift->end_time ?? now())
            ->where("branch_id", $cashier_shifts?->cashier_man?->branch_id)
            ->where("cahier_man_id", $cashier_shifts?->cashier_man?->id)
            ->with("financial_account")
            ->get();
            
            $delivery_financial_accounts = OrderFinancial::
            selectRaw("financial_id ,SUM(amount) as total_amount")
            ->whereIn("order_id", $delivery_orders)
            ->with("financials")
            ->groupBy("financial_id") 
            ->get();
            
            $due_module = Order:: 
            where("due_module", ">", 0)
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->sum("due_module");
            $due_user = Order:: 
            where("due", 1)
            ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->where("is_void", 0)  
            ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
            ->sum("amount");

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
            
            $expenses = Expense::
            selectRaw("financial_account_id, category_id, SUM(amount) AS total")
            ->where('created_at', '>=', $shift->start_time ?? now())
            ->where('created_at', '<=', $shift->end_time ?? now())
            ->where("branch_id", $cashier_shifts?->cashier_man?->branch_id)
            ->where("cahier_man_id", $cashier_shifts?->cashier_man?->id)
            ->with("financial_account", "category")
            ->groupBy("financial_account_id")
            ->groupBy("category_id")
            ->get()
            ->map(function($item){
                return [
                    "financial_account" => $item?->financial_account?->name,
                    "category" => $item?->category?->name,
                    "total" => $item->total,
                ];
            });
            $online_order_paid =  Order::
            selectRaw("payment_method_id, SUM(amount) AS amount")
            ->where("pos", 0)
            ->where(function($query){
                $query->where("payment_method_id", "!=", 2)
                ->where(function($q){
                    $q->where("status", 1)
                    ->orWhereNull("status");
                })
                ->orWhereHas("financial_accountigs");
            })
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->with("payment_method")
            ->groupBy("payment_method_id")
            ->groupBy("order_type")
            ->get()
            ->map(function($item){
                return [
                    "payment_method" => $item?->payment_method?->name,
                    "payment_method_id" => $item->payment_method_id,
                    "amount" => $item->amount,
                ];
            });
            $online_order_unpaid = Order::
            selectRaw("payment_method_id, SUM(amount) AS amount")
            ->where("pos", 0) 
            ->where("payment_method_id", 2)
            ->whereDoesntHave("financial_accountigs")
            ->where(function($q){
                $q->where("status", 1)
                ->orWhereNull("status");
            }) 
            ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
            ->with("payment_method")
            ->groupBy("payment_method_id")
            ->groupBy("order_type")
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

            if($cashier_shifts?->cashier_man?->report ?? 0 == "all"){
                $group_modules = Order::
                selectRaw("module_id, SUM(amount) AS amount, SUM(due_module) AS due_module, group_products.name AS module_name")
                ->join('group_products', 'group_products.id', '=', 'orders.module_id')
                ->with("group_module")
                ->groupBy("module_id", 'group_products.name')
                ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
                ->get()
                ->map(function($item){
                    return [
                        "amount" => $item->amount,
                        "due" => $item->due_module,
                        "module" => $item?->module_name,
                    ];
                });
                
                $captain_order = Order::
                selectRaw("
                    orders.captain_id, 
                    finantiol_acountings.id as financial_id,
                    finantiol_acountings.name as financial_name, 
                    SUM(order_financials.amount) AS total_financial
                ")
                ->join("order_financials", "orders.id", "=", "order_financials.order_id")
                ->join("finantiol_acountings", "finantiol_acountings.id", "=", "order_financials.financial_id")
                ->where("orders.pos", 1)
                ->whereNotNull("orders.captain_id")
                ->where(function($q) {
                    $q->where("orders.status", 1)
                    ->orWhereNull("orders.status");
                })
                ->where('orders.shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
                ->where("orders.is_void", 0)
                ->with('captain') // لجلب بيانات الكابتن (الاسم وغيره) من العلاقة
                ->groupBy("orders.captain_id", "finantiol_acountings.id", "finantiol_acountings.name")
                ->get();
                $start_balance = [
                    "amount" => $cashier_shift->amount ?? 0,
                    "financial" => $cashier_shifts?->financial?->name,
                ];
                $arr = [
                    "actual_total" => $actual_total,
                    "start_amount" => $start_amount,
                    "expenses" => $expenses, 
                    'perimission' => true,
                    'financial_accounts' => $financial_accounts,
                    'order_count' => $order_count,
                    'total_amount' => $total_amount, 
                    'expenses_total' => $expenses_total,
                    "total_orders" => $total_orders,  
                    'group_modules' => $group_modules, 
                    'expenses' => $expenses, 
                    'online_order' => $online_order,
                    'report_role' => $cashier_shifts?->cashier_man?->report ?? 0,
                    "void_order_count" => $void_order_count,
                    "void_order_sum" => $void_order_sum,
                    "captain_order" => $captain_order,
                    "due_module" => $due_module,
                    "due_user" => $due_user,
                    "start_balance" => $start_balance,
                    "net_cash_drawer" => $net_cash_drawer,
                ];
                if(isset($hall_orders)){
                    $arr['hall_orders'] = $hall_orders;
                }
                if($cashier_shifts?->cashier_man?->service_fees ?? 0){
                    $service_fees = Order::
                    select("id")
                    ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
                    ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
                    ->where("is_void", 0) 
                    ->where(function($query) {
                        $query->where('status', 1)
                        ->orWhereNull('status');
                    }) 
                    ->where(function($query){
                        $query->where('pos', 1)
                        ->orWhere('pos', 0)
                        ->where('order_status', '!=', 'pending');
                    }) 
                    ->where(function($query) {
                        $query->where('due_from_delivery', 0)
                        ->where('order_type', "delivery")
                        ->orwhere('due_from_delivery', 1)
                        ->where('order_type', "!=", "delivery");
                    }) 
                    ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                    ->sum('service_fees');
                    $arr['service_fees'] = $service_fees;
                }
                if($cashier_shifts?->cashier_man?->total_tax ?? 0){
                    $total_tax = Order::
                    select("id")
                    ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
                    ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
                    ->where("is_void", 0) 
                    ->where(function($query) {
                        $query->where('status', 1)
                        ->orWhereNull('status');
                    }) 
                    ->where(function($query){
                        $query->where('pos', 1)
                        ->orWhere('pos', 0)
                        ->where('order_status', '!=', 'pending');
                    })
      
                    ->where(function($query) {
                        $query->where('due_from_delivery', 0)
                        ->where('order_type', "delivery")
                        ->orwhere('due_from_delivery', 1)
                        ->where('order_type', "!=", "delivery");
                    }) 
                    ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                    ->sum('total_tax');
                    $arr['total_tax'] = $total_tax;
                } 
                if($cashier_shifts?->cashier_man?->enter_amount ?? 0){
                    $arr['gap'] = $gap;
                }
                $cashier_shifts->end_time = now();
                $cashier_shifts->save();
                return response()->json($arr);
            }
            elseif($cashier_shifts?->cashier_man?->report ?? 0 == "financial"){
                
               $captain_order = Order::
                selectRaw("
                    orders.captain_id, 
                    finantiol_acountings.id as financial_id,
                    finantiol_acountings.name as financial_name, 
                    SUM(order_financials.amount) AS total_financial
                ")
                ->join("order_financials", "orders.id", "=", "order_financials.order_id")
                ->join("finantiol_acountings", "finantiol_acountings.id", "=", "order_financials.financial_id")
                ->where("orders.pos", 1)
                ->whereNotNull("orders.captain_id")
                ->where(function($q) {
                    $q->where("orders.status", 1)
                    ->orWhereNull("orders.status");
                })
                ->where('orders.shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
                ->where("orders.is_void", 0)
                ->with('captain') // لجلب بيانات الكابتن (الاسم وغيره) من العلاقة
                ->groupBy("orders.captain_id", "finantiol_acountings.id", "finantiol_acountings.name")
                ->get();
                $start_balance = [
                    "amount" => $cashier_shifts->amount,
                    "financial" => $cashier_shifts?->financial?->name,
                ];
                $arr = [
                    "actual_total" => $actual_total,
                    "start_amount" => $start_amount,
                    "expenses" => $expenses, 
                    "total_orders" => $total_orders, 
                    'perimission' => true,
                    'financial_accounts' => $financial_accounts,
                    'report_role' => $cashier_shifts?->cashier_man?->report ?? 0,
                    'captain_order' => $captain_order,
                    "due_module" => $due_module,
                    "due_user" => $due_user,
                    "start_balance" => $start_balance,
                    "net_cash_drawer" => $net_cash_drawer,
                ];
                if(isset($hall_orders)){
                    $arr['hall_orders'] = $hall_orders;
                }
                if($cashier_shifts?->cashier_man?->enter_amount ?? 0){
                    $arr['gap'] = $gap;
                }
                if($cashier_shifts?->cashier_man?->service_fees ?? 0){
                    $service_fees = Order::
                    select("id")
                    ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
                    ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
                    ->where("is_void", 0) 
                    ->where(function($query) {
                        $query->where('status', 1)
                        ->orWhereNull('status');
                    }) 
                    ->where(function($query){
                        $query->where('pos', 1)
                        ->orWhere('pos', 0)
                        ->where('order_status', '!=', 'pending');
                    })
         
                    ->where(function($query) {
                        $query->where('due_from_delivery', 0)
                        ->where('order_type', "delivery")
                        ->orwhere('due_from_delivery', 1)
                        ->where('order_type', "!=", "delivery");
                    }) 
                    ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                    ->sum('service_fees');
                    $arr['service_fees'] = $service_fees;
                }
                if($cashier_shifts?->cashier_man?->total_tax ?? 0){
                    $total_tax = Order::
                    select("id")
                    ->where('cashier_man_id', $cashier_shifts?->cashier_man?->id ?? 0)
                    ->where('shift', $cashier_shifts?->cashier_man?->shift_number ?? 0)
                    ->where("is_void", 0) 
                    ->where(function($query) {
                        $query->where('status', 1)
                        ->orWhereNull('status');
                    }) 
                    ->where(function($query){
                        $query->where('pos', 1)
                        ->orWhere('pos', 0)
                        ->where('order_status', '!=', 'pending');
                    })
    
                    ->where(function($query) {
                        $query->where('due_from_delivery', 0)
                        ->where('order_type', "delivery")
                        ->orwhere('due_from_delivery', 1)
                        ->where('order_type', "!=", "delivery");
                    }) 
                    ->whereIn("order_status", ['pending', "confirmed", "processing", "out_for_delivery", "delivered", "scheduled"])
                    ->sum('total_tax');
                    $arr['total_tax'] = $total_tax;
                } 
                $cashier_shifts->end_time = now();
                $cashier_shifts->save();
                return response()->json($arr);
            }
        } 
 
    }
}
