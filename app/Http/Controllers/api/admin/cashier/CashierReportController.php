<?php

namespace App\Http\Controllers\api\admin\cashier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\CashierShift;
use App\Models\PaymentMethod;
use App\Models\Expense;
use App\Models\TimeSittings;
use App\Models\FinantiolAcounting;
use App\Models\OrderFinancial;
use App\Models\CashierBalance;

class CashierReportController extends Controller
{
    public function __construct(private CashierShift $cashier_shift,
    private Order $orders, private PaymentMethod $payment_methods
    , private TimeSittings $TimeSittings, private FinantiolAcounting $financial_account,
    private OrderFinancial $order_financial, private CashierBalance $cashier_balance,
    private Expense $expenses){}
    
    public function cashier_reports(Request $request){
        $validator = Validator::make($request->all(), [
            'from_date' => 'date',
            'to_date' => 'date',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $cashier_balance = $this->cashier_balance;
        $cashier_shift = $this->cashier_shift
        ->with('cashier_man')
        ->whereDate("start_time", ">=", $request->from_date ?? Carbon::parse("1999-05-05"))
        ->whereDate("start_time", "<=", $request->to_date ?? now())
        ->get();  
        $shifts_data = [];
        $data = [];
        $last_date = null;
        foreach ($cashier_shift as $item) {
            $date_format = Carbon::parse($item->start_time)->format("Y-m-d");
 
            $total_orders = Order::
            select("id") 
            ->where('shift', $item->shift)
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
            
            $expenses = $this->expenses
            ->where('created_at', ">=", $item->from_date)
            ->where('created_at', "<=", $item->to_date)
            ->sum('amount');
            $start_amount = $item->amount ?? 0; 
            $expenses = $expenses; 
            $actual_total = $total_orders + $start_amount - $expenses;
            if($last_date == $date_format){ 
                $data[$date_format] = [
                    "expenses" => $expenses + $data[$date_format]['expenses'],
                    "date" => $last_date,
                    "actual_total" => $actual_total + $data[$date_format]['actual_total'],
                    "total_orders" => $total_orders + $data[$date_format]['total_orders'],
                    "shift_num" => $shift_num
                ];
            }
            else{ 
                $data[$date_format] = [
                    "expenses" => $expenses,
                    "date" => $last_date,
                    "actual_total" => $actual_total,
                    "total_orders" => $total_orders,
                    "shift_num" => $shift_num
                ];
            }
            $last_date = $date_format;
        } 

        return response()->json([
            'data' => array_values($data),
        ]);
    }

    public function shifts_data(Request $request){
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $cashier_balance = $this->cashier_balance;
        $cashier_shift = $this->cashier_shift
        ->with('cashier_man')
        ->whereDate("start_time" , $request->date)
        ->get();  
        $shifts_data = [];
        $data = [];
        $last_date = null;
        foreach ($cashier_shift as $item) { 
       
            $shifts_data = $this->cashier_shift
            ->whereDate("start_time" , $request->date)
            ->get();
            $shift_ids = $shifts_data
            ->pluck("shift")
            ->toArray();
            $total_orders = Order::
            select("id") 
            ->whereIn('shift', $shift_ids)
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
            $count_orders = Order::
            select("id") 
            ->whereIn('shift', $shift_ids)
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
            ->count();
            
            $expenses = $this->expenses
            ->whereDate('created_at', $item->start_time)
            ->sum('amount');
            $start_amount = $shifts_data->sum('amount') ?? 0; 
            $expenses = $expenses; 
            $actual_total = $total_orders + $start_amount - $expenses;
            $data[] = [
                "id" => $item->id,
                "start_shift" => $item->start_time,
                "end_shift" => $item->end_time,
                "expenses" => $expenses, 
                "actual_total" => $actual_total,
                "total_orders" => $total_orders,
                "count_orders" => $count_orders,
            ];
        } 

        return response()->json([
            'data' => $data,
        ]);
    }

    public function shift_details(Request $request, $id){
  
        $cashier_shift = $this->cashier_shift
        ->with('cashier_man')
        ->where("id" , $id)
        ->get();  
        $shifts_data = [];
        $data = [];
        $last_date = null; 

        $total_orders = Order::
        select("id") 
        ->where('shift', $cashier_shift->shift)
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
            $count_orders = Order::
            select("id") 
            ->whereIn('shift', $cashier_shift->shift)
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
            ->count();
            
            $expenses = $this->expenses
            ->whereDate('created_at', $cashier_shift->start_time)
            ->sum('amount');
            $start_amount = $shifts_data->sum('amount') ?? 0; 
            $expenses = $expenses; 
            $actual_total = $total_orders + $start_amount - $expenses;
            $data[] = [
                "start_shift" => $cashier_shift->start_time,
                "end_shift" => $cashier_shift->end_time,
                "expenses" => $expenses, 
                "actual_total" => $actual_total,
                "total_orders" => $total_orders,
                "count_orders" => $count_orders,
            ]; 

        return response()->json([
            'data' => $data,
        ]);
    }

    public function all_cashiers(Request $request){
        $validator = Validator::make($request->all(), [
            'from_date' => 'date',
            'to_date' => 'date',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $time_sittings = $this->TimeSittings 
        ->get();
        if ($time_sittings->count() > 0) {
            $from_time = $time_sittings[0]->from;
            $date_to = $request->date_to; 
            $end = $date_to . ' ' . $time_sittings[$time_sittings->count() - 1]->from;
            $hours = $time_sittings[$time_sittings->count() - 1]->hours;
            $minutes = $time_sittings[$time_sittings->count() - 1]->minutes;
            $from = $request->date . ' ' . $from_time;
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
            // }
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        } 
        $orders = $this->orders
        ->select('cashier_id', 'payment_method_id', 'amount')
        ->where('pos', 1)
        ->whereBetween('created_at', [$start, $end])
        ->whereNotNull('cashier_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        });
        if (!empty($request->from_date)) {
            $orders = $orders->where('created_at', '>=', $request->from_date);
        }
        if (!empty($request->to_date)) {
            $orders = $orders->where('created_at', '<=', $request->to_date);
        }
        $orders = $orders->get();
        $payments = [];
        $payments_data = [];
        foreach ($orders as $item) {
            $payments[$item->casheir->name] = [];
            if (isset($payments[$item->casheir->name][$item->payment_method->name])) {
                $payments[$item->casheir->name][$item->payment_method->name]['amount'] += $item->amount;
            } else {
                $payments[$item->casheir->name][$item->payment_method->name]['amount'] = $item->amount;
            }
            
        }
        $iter = 0;
        $iter2 = 0;
        foreach ($payments as $key => $item) {
            $payments_data[$iter]['cashier'] = $key;
            foreach ($item as $key2 => $element) {
                $payments_data[$iter]['payment_methods'][] = [
                    'name' => $key2,
                    'amount' => $element['amount'],
                ]; 
            } 
            $iter++;
        }

        return response()->json([
            'payments' => $payments_data
        ]);
    }

    public function branch_cashiers(Request $request){
        $validator = Validator::make($request->all(), [
            'from_date' => 'date',
            'to_date' => 'date',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $time_sittings = $this->TimeSittings 
        ->get();
        if ($time_sittings->count() > 0) {
            $from_time = $time_sittings[0]->from;
            $date_to = $request->date_to; 
            $end = $date_to . ' ' . $time_sittings[$time_sittings->count() - 1]->from;
            $hours = $time_sittings[$time_sittings->count() - 1]->hours;
            $minutes = $time_sittings[$time_sittings->count() - 1]->minutes;
            $from = $request->date . ' ' . $from_time;
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
        $orders = $this->orders
        ->select('cashier_id', 'payment_method_id', 'amount')
        ->where('pos', 1)
        ->where('branch_id', $request->user()->branch_id)
        ->whereBetween('created_at', [$start, $end])
        ->whereNotNull('cashier_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        });
        if (!empty($request->from_date)) {
            $orders = $orders->where('created_at', '>=', $request->from_date);
        }
        if (!empty($request->to_date)) {
            $orders = $orders->where('created_at', '<=', $request->to_date);
        }
        $orders = $orders->get();
        $payments = [];
        $payments_data = [];
        foreach ($orders as $item) {
            $payments[$item->casheir->name] = [];
            if (isset($payments[$item->casheir->name][$item->payment_method->name])) {
                $payments[$item->casheir->name][$item->payment_method->name]['amount'] += $item->amount;
            } else {
                $payments[$item->casheir->name][$item->payment_method->name]['amount'] = $item->amount;
            }
            
        }
        $iter = 0;
        $iter2 = 0;
        foreach ($payments as $key => $item) {
            $payments_data[$iter]['cashier'] = $key;
            foreach ($item as $key2 => $element) {
                $payments_data[$iter]['payment_methods'][] = [
                    'name' => $key2,
                    'amount' => $element['amount'],
                ]; 
            } 
            $iter++;
        }

        return response()->json([
            'payments' => $payments_data
        ]);
    }

    public function shift_branch_reports(Request $request){
        // /cashier/reports/shift_branch
        $branch_id = $request->user()->branch_id;
        $cashier_shift = $this->cashier_shift
        ->whereHas('cashier_man', function($query) use($branch_id){
            $query->where('branch_id', $branch_id);
        })
        ->with('cashier_man')
        ->get();
       $orders = $this->orders
       ->whereNotNull('shift')
       ->orderByDesc('shift')
       ->orderBy('payment_method_id')
       ->where('status', 1)
       ->get();
    //    return response()->json([
    //     'orders' => $orders
    //    ]);
        $shifts_data = [];
        foreach ($cashier_shift as $item) {
            $orders_shift = $orders->where('shift', $item->shift)->values();
            $products_shift = collect([]);
            foreach ($orders_shift as $key => $element) {
                $products_element = collect($element->order_details)->count() > 0
                ?collect($element->order_details)[0]?->product : null;
                $products_element = collect($products_element)
                ->map(function($item){
                    return [
                        'product_id' => $item?->product?->id ?? null,
                        'product_item' => $item?->product?->name ?? null,
                        'count' => $item?->count ?? 0,
                    ];
                });
                if ($products_element->count() > 0) {
                    $products_shift[] = $products_element;
                }
            }
            $products_shift = collect($products_shift)->flatten(1);
            $products_items = [];
            foreach ($products_shift as $element) {
                if(isset($products_items[$element['product_id']])){
                    $products_items[$element['product_id']] = [
                        'product_id' => $element?->product?->id ?? null,
                        'product_item' => $element?->product?->name ?? null,
                        'count' => ($element?->count ?? 0) +( $products_items[$element['product_id']]?->count ?? 0),
                    ];
                }
                else{
                    $products_items[$element['product_id']] = [
                        'product_id' => $element?->product?->id ?? null,
                        'product_item' => $element?->product?->name ?? null,
                        'count' => $element?->count ?? 0,
                    ];
                }
            }
            $products_items = collect($products_items)->sortByDesc('count');
            $payment_methods = $this->payment_methods
            ->where('status', 1)
            ->get();
            $shifts_data[$item->shift] = [
                'shift' => $item,
                'orders' => $orders_shift,
                'cashier_men' => $cashier_shift
                ->where('shift', $item->shift)
                ->pluck('cashier_man'),
                'orders_count' => count($orders_shift),
                'avarage_order' => count($orders_shift) > 0 ?
                 $orders_shift->sum('amount') / count($orders_shift) : 0,
                'product_items' => $products_items->values(),
                'products_items_count' => count($products_items),
                'cashier_men' => $cashier_shift
                ->where('shift', $item->shift)
                ->values()->map(function($cashier_item) use($orders_shift, $payment_methods){
                    $cashier_item->cashier_orders = 
                    $orders_shift->where('cashier_man_id', $cashier_item->cashier_man_id)
                    ->values();
                    $cashier_item->total_orders = $cashier_item->cashier_orders->sum('amount');
                    $payment_methods_data = [];
                    foreach ($payment_methods as $item) {
                        $payment_methods_data[] = [
                            'payment_method' => $item->name,
                            'amount' => $cashier_item->cashier_orders
                            ->where('payment_method_id', $item->id)
                            ->sum('amount'),
                            'orders' => $cashier_item->cashier_orders
                            ->where('payment_method_id', $item->id)
                            ->values()
                        ];
                    }
                    $cashier_item->payment_methods_data = $payment_methods_data;

                    return $cashier_item;
                }),

            ];
        }
        $shifts_data = collect($shifts_data)->values()
        ->map(function($element) use($payment_methods){
            $payment_methods_data = [];
            $cashier_orders = $this->orders
            ->where('shift', $element['shift'])
            ->get();
            $payment_methods_total = [];
            foreach ($payment_methods as $item) {
                $payment_methods_total[] = [
                    'payment_method' => $item->name,
                    'amount' => $cashier_orders
                    ->where('payment_method_id', $item->id)
                    ->sum('amount'),
                    'orders' => $cashier_orders
                    ->where('payment_method_id', $item->id)
                    ->values()
                ];
            }
            $element['payment_methods_total'] = $payment_methods_total;
            return $element;
        });

        return response()->json([
            'shifts_data' => $shifts_data,
        ]);
    }

    public function shift_reports(){
        // /cashier/reports/shift_branch
        // الفلوس payment methods
        // orders, average
        // items
        $cashier_shift = $this->cashier_shift
        ->with('cashier_man')
        ->get();
       $orders = $this->orders
       ->whereNotNull('shift')
       ->orderByDesc('shift')
       ->orderBy('payment_method_id')
       ->where('status', 1)
       ->get();
    //    return response()->json([
    //     'orders' => $orders
    //    ]);
        $shifts_data = [];
        foreach ($cashier_shift as $item) {
            $orders_shift = $orders->where('shift', $item->shift)->values();
            $products_shift = collect([]);
            foreach ($orders_shift as $key => $element) {
                $products_element = collect($element->order_details)->count() > 0
                ?collect($element->order_details)[0]?->product : null;
                $products_element = collect($products_element)
                ->map(function($item){
                    return [
                        'product_id' => $item?->product?->id ?? null,
                        'product_item' => $item?->product?->name ?? null,
                        'count' => $item?->count ?? 0,
                    ];
                });
                if ($products_element->count() > 0) {
                    $products_shift[] = $products_element;
                }
            }
            $products_shift = collect($products_shift)->flatten(1);
            $products_items = [];
            foreach ($products_shift as $element) {
                if(isset($products_items[$element['product_id']])){
                    $products_items[$element['product_id']] = [
                        'product_id' => $element?->product?->id ?? null,
                        'product_item' => $element?->product?->name ?? null,
                        'count' => ($element?->count ?? 0) +( $products_items[$element['product_id']]?->count ?? 0),
                    ];
                }
                else{
                    $products_items[$element['product_id']] = [
                        'product_id' => $element?->product?->id ?? null,
                        'product_item' => $element?->product?->name ?? null,
                        'count' => $element?->count ?? 0,
                    ];
                }
            }
            $products_items = collect($products_items)->sortByDesc('count');
            $payment_methods = $this->payment_methods
            ->where('status', 1)
            ->get();
            $shifts_data[$item->shift] = [
                'shift' => $item,
                'orders' => $orders_shift,
                'cashier_men' => $cashier_shift
                ->where('shift', $item->shift)
                ->pluck('cashier_man'),
                'orders_count' => count($orders_shift),
                'avarage_order' => count($orders_shift) > 0 ?
                 $orders_shift->sum('amount') / count($orders_shift) : 0,
                'product_items' => $products_items->values(),
                'products_items_count' => count($products_items),
                'cashier_men' => $cashier_shift
                ->where('shift', $item->shift)
                ->values()->map(function($cashier_item) use($orders_shift, $payment_methods){
                    $cashier_item->cashier_orders = 
                    $orders_shift->where('cashier_man_id', $cashier_item->cashier_man_id)
                    ->values();
                    $cashier_item->total_orders = $cashier_item->cashier_orders->sum('amount');
                    $payment_methods_data = [];
                    foreach ($payment_methods as $item) {
                        $payment_methods_data[] = [
                            'payment_method' => $item->name,
                            'amount' => $cashier_item->cashier_orders
                            ->where('payment_method_id', $item->id)
                            ->sum('amount'),
                            'orders' => $cashier_item->cashier_orders
                            ->where('payment_method_id', $item->id)
                            ->values()
                        ];
                    }
                    $cashier_item->payment_methods_data = $payment_methods_data;

                    return $cashier_item;
                }),

            ];
        }
        $shifts_data = collect($shifts_data)->values()
        ->map(function($element) use($payment_methods){
            $payment_methods_data = [];
            $cashier_orders = $this->orders
            ->where('shift', $element['shift'])
            ->get();
            $payment_methods_total = [];
            foreach ($payment_methods as $item) {
                $payment_methods_total[] = [
                    'payment_method' => $item->name,
                    'amount' => $cashier_orders
                    ->where('payment_method_id', $item->id)
                    ->sum('amount'),
                    'orders' => $cashier_orders
                    ->where('payment_method_id', $item->id)
                    ->values()
                ];
            }
            $element['payment_methods_total'] = $payment_methods_total;
            return $element;
        });

        return response()->json([
            'shifts_data' => $shifts_data,
        ]);
    }
}
