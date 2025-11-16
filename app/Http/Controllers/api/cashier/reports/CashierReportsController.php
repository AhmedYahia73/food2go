<?php

namespace App\Http\Controllers\api\cashier\reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\CashierShift;
use App\Models\PaymentMethod;
use App\Models\TimeSittings;
use App\Models\FinantiolAcounting;
use App\Models\OrderFinancial;
use App\Models\CashierBalance; 

class CashierReportsController extends Controller
{
    public function __construct(private CashierShift $cashier_shift,
    private Order $orders, private PaymentMethod $payment_methods
    , private TimeSittings $TimeSittings, private FinantiolAcounting $financial_account,
    private OrderFinancial $order_financial, private CashierBalance $cashier_balance,){}
    
    public function cashier_reports(Request $request){
    //     $cashier_balance = $this->cashier_balance;
    //     $cashier_shift = $this->cashier_shift
    //     ->with('cashier_man')
    //     ->get();
    //     $orders = $this->orders
    //     ->whereNotNull('shift')
    //     ->where('order_type', '!=', 'delivery')
    //     ->orderByDesc('shift')
    //     ->orderBy('payment_method_id')
    //     ->where('status', 1);
    //     if($request->from_date){
    //         $orders = $orders
    //         ->whereDate('created_at', '>=', $request->from_date);
    //     }
    //     if($request->to_date){
    //         $orders = $orders
    //         ->whereDate('created_at', '<=', $request->to_date);
    //     }
    //     $orders = $orders->get();
    // //    return response()->json([
    // //     'orders' => $orders
    // //    ]);
    //     $shifts_data = [];
    //     foreach ($cashier_shift as $item) {
    //         $orders_shift = $orders->where('shift', $item->shift)->values();
    //         $products_shift = collect([]);
    //         foreach ($orders_shift as $key => $element) {
    //             $products_element = collect($element->order_details_data_data)->count() > 0
    //             ?collect($element->order_details_data_data)[0]?->product : null;
    //             $products_element = collect($products_element)
    //             ->map(function($item){
    //                 return [
    //                     'product_id' => $item?->product?->id ?? null,
    //                     'product_item' => $item?->product?->name ?? null,
    //                     'count' => $item?->count ?? 0,
    //                 ];
    //             });
    //             if ($products_element->count() > 0) {
    //                 $products_shift[] = $products_element;
    //             }
    //         }
    //         $products_shift = collect($products_shift)->flatten(1);
    //         $products_items = [];
    //         foreach ($products_shift as $element) {
    //             if(isset($products_items[$element['product_id']])){
    //                 $products_items[$element['product_id']] = [
    //                     'product_id' => $element?->product?->id ?? null,
    //                     'product_item' => $element?->product?->name ?? null,
    //                     'count' => ($element?->count ?? 0) +( $products_items[$element['product_id']]?->count ?? 0),
    //                 ];
    //             }
    //             else{
    //                 $products_items[$element['product_id']] = [
    //                     'product_id' => $element?->product?->id ?? null,
    //                     'product_item' => $element?->product?->name ?? null,
    //                     'count' => $element?->count ?? 0,
    //                 ];
    //             }
    //         }
    //         $products_items = collect($products_items)->sortByDesc('count');
    //         $financial_account = $this->financial_account
    //         ->where('status', 1)
    //         ->get();
    //         $shifts_data[$item->shift] = [
    //             'shift' => $item,
    //             'orders' => $orders_shift,
    //             'cashier_men' => $cashier_shift
    //             ->where('shift', $item->shift)
    //             ->pluck('cashier_man'),
    //             'orders_count' => count($orders_shift),
    //             'avarage_order' => count($orders_shift) > 0 ?
    //              $orders_shift->sum('amount') / count($orders_shift) : 0,
    //             'product_items' => $products_items->values(),
    //             'products_items_count' => count($products_items),
    //             'cashier_men' => $cashier_shift
    //             ->where('shift', $item->shift)
    //             ->values()->map(function($cashier_item) use($orders_shift, $financial_account, $item, $cashier_balance){
    //                 $shift_num = $item->shift;
    //                 $cashier_item->cashier_orders = 
    //                 $orders_shift->where('cashier_man_id', $cashier_item->cashier_man_id)
    //                 ->values();
    //                 $cashier_item->total_orders = $cashier_item->cashier_orders->sum('amount') + 
    //                 $cashier_balance->where('shift_number', $shift_num)->sum('balance');
    //                 // + delivery cash
    //                 $financial_accounts_data = [];
    //                 foreach ($financial_account as $item) {
    //                     $financial_order = $this->order_financial
    //                         ->with('order')
    //                         ->where('financial_id', $item->id)
    //                         ->whereHas('order', function($query) use($shift_num) {
    //                             $query->where('shift', $shift_num);
    //                         })
    //                         ->get();
    //                     $financial_accounts_data[] = [
    //                         'financial_account' => $item->name,
    //                         'amount' => ($financial_order?->sum('amount') ?? 0),
    //                         'orders' => $financial_order?->pluck('order') ?? [],
    //                     ];
    //                 }
    //                 $cashier_item->financial_accounts_data = $financial_accounts_data;

    //                 return $cashier_item;
    //             }),

    //         ];
    //     }
    //     $shifts_data = collect($shifts_data)->values()
    //     ->map(function($element) use($financial_account){
    //         $financial_accounts_data = [];
    //         $financial_account_total = [];
    //         foreach ($financial_account as $item) {
    //             $cashier_orders = $this->orders
    //             ->where('shift', $element['shift'])
    //             ->whereHas('financial_accountigs', function($query) use($item) {
    //                 $query->where('finantiol_acountings.id', $item->id);
    //             })
    //             ->with('financial_accountigs')
    //             ->get();
    //             $financial_account_total[] = [
    //                 'financial_account' => $item->name,
    //                 'amount' => $cashier_orders
    //                 ?->pluck('financial_accountigs')
    //                 ?->sum('amount') ?? 0,
    //                 'orders' => $cashier_orders
    //             ];
    //         }
    //         $element['financial_account_total'] = $financial_account_total;
    //         return $element;
    //     });
    // Preload data in minimal queries 
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $cashier_shifts = $this->cashier_shift
        ->with('cashier_man:id,shift_number,user_name');
        if($request->start_date){
            $cashier_shifts = $cashier_shifts->whereDate('start_time', '>=', $request->start_date);
        }
        if($request->end_date){
            $cashier_shifts = $cashier_shifts->whereDate('end_time', '<=', $request->end_date);
        }
        $cashier_shifts = $cashier_shifts->get();
   

        return response()->json([
            'cashier_shifts' => $cashier_shifts,
        ]);

    }

    public function branch_cashier_reports(Request $request){
        $validator = Validator::make($request->all(), [
            'start_date' => 'date',
            'end_date' => 'date',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $cashier_shifts = $this->cashier_shift
        ->with('cashier_man:id,shift_number,user_name');
        if($request->start_date){
            $cashier_shifts = $cashier_shifts->whereDate('start_time', '>=', $request->start_date);
        }
        if($request->end_date){
            $cashier_shifts = $cashier_shifts->whereDate('end_time', '<=', $request->end_date);
        }
        $cashier_shifts = $cashier_shifts->get();


        return response()->json([
            'cashier_shifts' => $cashier_shifts,
        ]);
    }
    
    
    public function shift_cashier_reports(Request $request, $id){
 
        $cashier_balance = $this->cashier_balance->get();
        $financial_account = $this->financial_account
        ->where('status', 1)
        ->get();

        $cashier_shifts = $this->cashier_shift
            ->with('cashier_man.branch')
            ->where('id', $id)
            ->get();
        if($cashier_shifts-> count() == 0){
            return response()->json([
                'errors' => 'id is wrong'
            ], 400);
        }
        $ordersQuery = $this->orders
        ->with('financial_accountigs')
        ->whereNotNull('shift')
        ->where('order_type', '!=', 'delivery')
        ->where('status', 1)
        ->where('shift', $cashier_shifts[0]->shift)
        ->where('order_active', 1)
        ->orderBy('payment_method_id');


        $orders = $ordersQuery->get()->groupBy('shift');
        unset($orders->order_details);
        $orderFinancials = $this->order_financial
            ->with('order')
            ->whereIn('financial_id', $financial_account->pluck('id'))
            ->get()
            ->groupBy('financial_id');

        $shifts_data = $cashier_shifts->map(function ($shift) use (
            $orders,
            $cashier_shifts,
            $cashier_balance,
            $financial_account,
            $orderFinancials
        ) {
            $shift_num = $shift->shift;
           $shift_orders = collect($orders->get($shift_num, collect()))
            ->select('id', 'amount', 'order_type', 'total_tax', 'total_discount',
            'coupon_discount', 'order_number', 'order_details_data', 'source', 'shift', 'cashier_man_id', 'financial_accountigs');

            $products_items = $shift_orders
                ->flatMap(function ($order) {
                    return collect($order['order_details_data'] ?? [])
                        ->map(function ($item) {
                            return [
                                'product_id'   => $item['product'][0]['product']['id'] ?? null,
                                'product_item' => $item['product'][0]['product']['name'] ?? null,
                                'count'        => $item['product'][0]['count'] ?? 0,
                            ];
                        });
                })
                ->groupBy('product_id')
                ->map(function ($group, $productId) {
                    return [
                        'product_id'   => $productId,
                        'product_item' => $group->first()['product_item'],
                        'count'        => $group->sum('count'),
                    ];
                })
                ->sortByDesc('count')
                ->values();

            $cashiers_in_shift = $cashier_shifts
                ->where('shift', $shift_num)
                ->map(function ($cashier_item) use ($shift_orders, $cashier_balance, $financial_account, $orderFinancials, $shift_num) {
                    $cashier_orders = $shift_orders->where('cashier_man_id', $cashier_item->cashier_man_id);

                    $cashier_item->cashier_orders = $cashier_orders->values();
                    $cashier_item->total_orders =
                        $cashier_orders->sum('amount') +
                        $cashier_balance->where('shift_number', $shift_num)->sum('balance');

                    $cashier_item->financial_accounts_data = $financial_account->map(function ($fa) use ($orderFinancials, $shift_num) {
                        $ordersForAccount = $orderFinancials->get($fa->id, collect())
                            ->filter(fn($of) => $of->order?->shift == $shift_num);

                        return [
                            'financial_account' => $fa->name,
                            'amount'            => $ordersForAccount->sum('amount'),
                            'orders'            => $ordersForAccount->pluck('order'),
                        ];
                    })->values();

                    return [
                        'cashier_orders' => $cashier_item->cashier_orders,
                        'total_orders' => $cashier_item->total_orders,
                        'financial_accounts_data' => $cashier_item->financial_accounts_data,
                        'cashier_man_id' => $cashier_item?->cashier_man?->id,
                        'cashier_man' => $cashier_item?->cashier_man?->user_name,
                        'branch' => $cashier_item?->cashier_man?->branch?->name,
                    ];
                });
            $financial_account_total = $financial_account->map(function ($fa) use ($shift_orders) {
                $ordersForAccount = $shift_orders->filter(function ($order) use ($fa) {
                    return collect($order['financial_accountigs'])->contains('id', $fa->id);
                });

                return [
                    'financial_account' => $fa->name,
                    'amount'            => $ordersForAccount->flatMap->financial_accountigs->sum('amount'),
                    'orders'            => $ordersForAccount,
                ];
            });

            return [
                'id'                      => $shift->id,
                'shift'                   => $shift->shift,
                'start_time'              => $shift->start_time,
                'end_time'                => $shift->end_time,
                'total_amount_orders'     => $shift->total_orders,
                'orders'                  => $shift_orders->select('id', 'amount', 'order_type', 'order_number'),
                'orders_count'            => $shift_orders->count(),
                'avarage_order'           => $shift_orders->avg('amount') ?? 0,
                'product_items'           => $products_items,
                'products_items_count'    => $products_items->count(),
                'cashier_men'             => $cashiers_in_shift,
                'financial_account_total' => $financial_account_total,
            ];
        })->values();

        return response()->json([
            'shifts_data' => $shifts_data[0],
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
        ->where('order_active', 1)
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
        ->where('order_active', 1)
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
        ->where('order_active', 1)
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
                $products_element = collect($element->order_details_data)->count() > 0
                ?collect($element->order_details_data)[0]->product : null;
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
             ->where('order_active', 1)
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
        ->where('order_active', 1)
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
                $products_element = collect($element->order_details_data)->count() > 0
                ?collect($element->order_details_data)[0]?->product : null;
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
            ->where('order_active', 1)
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

    public function financial_report(Request $request){
        // Order
        if($request->report){
            $orders = Order::
            select("id")
            ->where('cashier_man_id', $request->user()->id)
            ->where('shift', $request->user()->shift_number)
            ->get();
            $orders = $orders 
            ?->pluck("id")?->toArray() ?? [];
            
            $shift = $this->cashier_shift
            ->where('shift', $request->user()->shift_number)
            ->where('cashier_man_id', $request->user()->id)
            ->first();
            $expenses = $this->expenses
            ->where('created_at', '>=', $shift->start_time ?? now())
            ->where('created_at', '<=', $shift->end_time ?? now());
            
            $financial_accounts = OrderFinancial::
            selectRaw("financial_id, SUM(amount) as total_amount")
            ->whereIn("order_id", $orders)
            ->with("financials")
            ->groupBy("financial_id")
            ->get()
            ->map(function($item) use($expenses) {
                $expenses_amount = $expenses
                ->where("financial_account_id", $item->financial_id)
                ->sum("amount") ?? 0;
                return [
                    "total_amount" => $item->total_amount - $expenses_amount,
                    "financial_id" => $item->financial_id,
                    "financial_name" => $item?->financials?->name,
                ];
            });

            return response()->json([
                'perimission' => true,
                'financial_accounts' => $financial_accounts
            ]);
        }

        return response()->json([
            'perimission' => false,
            'financial_accounts' => null
        ], 400);
    }
}
