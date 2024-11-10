<?php

namespace App\Http\Controllers\api\customer\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\order\OrderRequest;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductSale;

class MakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetail $order_details,
    private ProductSale $product_sales){}
    protected $orderRequest = [
        'date',
        'branch_id',
        'amount',
        'payment_status',
        'total_tax',
        'total_discount',
        'address_id',
        'order_type',
        'paid_by',
        'notes',
        'coupon_discount',
    ];

    public function order(OrderRequest $request){
        // https://bcknd.food2go.online/customer/make_order
        // Keys
        // date, branch_id, amount, coupon_discount, payment_status [paid, unpaid], total_tax, total_discount, address_id
        // order_type, paid_by, notes
        // deal[{deal_id, count}]
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], variation[{variation_id, option_id[]}], count}]
        $orderRequest = $request->only($this->orderRequest);
        $user = $request->user();
        $orderRequest['user_id'] = $user->id;
        $orderRequest['order_status'] = 'pending';
        $order = $this->order
        ->create($orderRequest);
        $user->address()->attach($request->address_id);
        $user->save();
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $key => $product) {
                for ($i=0, $end = $product['count']; $i < $end; $i++) { 
                    $order->products()->attach($product['product_id']);
                    $this->product_sales->create([
                        'product_id' => $product['product_id'],
                        'count' => $product['count'],
                        'date' => date('Y-m-d')
                    ]);
                }
                $this->order_details
                ->create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'count' => $product['count'],
                    'product_index' => $key,
                ]); // Add product with count
                if (isset($product['exclude_id'])) {
                    foreach ($product['exclude_id'] as $exclude) {
                        $this->order_details
                        ->create([
                            'order_id' => $order->id,
                            'product_id' => $product['product_id'],
                            'exclude_id' => $exclude,
                            'count' => $product['count'],
                            'product_index' => $key,
                        ]); // Add excludes
                    }
                }
                if (isset($product['addons'])) {
                    foreach ($product['addons'] as $addon) {
                        $this->order_details
                        ->create([
                            'order_id' => $order->id,
                            'product_id' => $product['product_id'],
                            'addon_id' => $addon['addon_id'],
                            'count' => $product['count'],
                            'addon_count' => $addon['count'],
                            'product_index' => $key,
                        ]); // Add excludes
                    }
                }
                if (isset($product['extra_id'])) {
                    foreach ($product['extra_id'] as $extra) {
                        $this->order_details
                        ->create([
                            'order_id' => $order->id,
                            'product_id' => $product['product_id'],
                            'extra_id' => $extra,
                            'count' => $product['count'],
                            'product_index' => $key,
                        ]); // Add extra
                    }
                }
                if (isset($product['variation'])) {
                    foreach ($product['variation'] as $variation) {
                        foreach ($variation['option_id'] as $option_id) {
                            $this->order_details
                            ->create([
                                'order_id' => $order->id,
                                'product_id' => $product['product_id'],
                                'variation_id' => $variation['variation_id'],
                                'option_id' => $option_id,
                                'count' => $product['count'],
                                'product_index' => $key,
                            ]); // Add variations & options
                        }
                    }
                }
            }
        }
        if ($request->deal) {
            $request->deal = is_string($request->deal) ? json_decode($request->deal) : $request->deal;
            foreach ($request->deal as $item) {
                $order->deal()->attach($item['deal_id']);
                $this->order_details
                ->create([
                    'order_id' => $order->id,
                    'deal_id' => $item['deal_id'],
                    'count' => $item['count'],
                ]); // Add excludes
            }
        }

        return response()->json([
            'success' => 'You make order success'
        ]);
    }
}
