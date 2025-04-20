<?php

namespace App\Http\Controllers\api\admin\pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\CashierShift;
use App\Models\PaymentMethod;

class PosReportsController extends Controller
{
    public function __construct(private CashierShift $cashier_shift,
    private Order $orders, private PaymentMethod $payment_methods){}

    public function shift_reports(){
        // /admin/pos/reports/shift_reports
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
