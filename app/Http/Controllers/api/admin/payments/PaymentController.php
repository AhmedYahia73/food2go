<?php

namespace App\Http\Controllers\api\admin\payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class PaymentController extends Controller
{
    public function __construct(private Order $orders){}

    public function pendding(){
        $orders_details = $this->orders
        ->whereNull('status')
        ->with(['details' => function($query){
            $query->with(['addon', 'product', 'exclude', 'extra', 'variation', 'option']);
        }, 'user'])
        ->get();
        $orders = [];
        foreach ($orders_details as $key => $item) {
            $orders[$key]['id'] = $item->id;
            $orders[$key]['date'] = $item->date;
            $orders[$key]['amount'] = $item->amount;
            $orders[$key]['order_status'] = $item->order_status;
            $orders[$key]['order_type'] = $item->order_type;
            $orders[$key]['payment_status'] = $item->payment_status;
            $orders[$key]['total_tax'] = $item->total_tax;
            $orders[$key]['total_discount'] = $item->total_discount;
            $orders[$key]['pos'] = $item->pos;
            $orders[$key]['notes'] = $item->notes;
            $orders[$key]['coupon_discount'] = $item->coupon_discount;
            $orders[$key]['order_number'] = $item->order_number;
            $orders[$key]['receipt'] = $item->receipt;
            $orders[$key]['user'] = $item->user;
            foreach ($item->details as $k => $element) {
                if (!empty($element->product)) {
                    $orders[$key]['user'][$element->product_index . '-' . $element->product_id]['product']
                    = $element->product;
                }
                if (!empty($element->addon)) {
                    $orders[$key]['user'][$element->product_index . '-' . $element->product_id]['addon']
                    = $element->addon;
                }
                if (!empty($element->exclude)) {
                    $orders[$key]['user'][$element->product_index . '-' . $element->product_id]['exclude']
                    = $element->exclude;
                }
                if (!empty($element->extra)) {
                    $orders[$key]['user'][$element->product_index . '-' . $element->product_id]['extra']
                    = $element->extra;
                }
                if (!empty($element->option)) {
                    $orders[$key]['user'][$element->product_index . '-' . $element->product_id]['product']
                    = $element->product;
                }
            }
        }

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function history(){
        $orders = $this->orders
        ->whereNotNull('status')
        ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }
}
