<?php

namespace App\Http\Controllers\api\customer\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\order\OrderRequest;
use App\trait\image;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductSale;
use App\Models\Product;
use App\Models\ExcludeProduct;
use App\Models\ExtraProduct;
use App\Models\VariationProduct;
use App\Models\OptionProduct;
use App\Models\Addon;

class MakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetail $order_details,
    private ProductSale $product_sales, private Product $products, private ExcludeProduct $excludes,
    private ExtraProduct $extras, private Addon $addons, private VariationProduct $variation,
    private OptionProduct $options){}
    use image;
    protected $orderRequest = [
        'date',
        'branch_id',
        'amount',
        'total_tax',
        'total_discount',
        'address_id',
        'order_type',
        'payment_method_id',
        'notes',
        'coupon_discount',
    ];

    public function order(OrderRequest $request){
        // https://bcknd.food2go.online/customer/make_order
        // Keys
        // date, branch_id, amount, coupon_discount, total_tax, total_discount, address_id
        // order_type[take_away,dine_in,delivery], notes
        // deal[{deal_id, count}], payment_method_id, receipt
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], variation[{variation_id, option_id[]}], count}]
        $orderRequest = $request->only($this->orderRequest);
        $user = $request->user();
        $orderRequest['user_id'] = $user->id;
        $orderRequest['order_status'] = 'pending';
        $points = 0;
        $order_details = [];
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $product) {
                $item = $this->products
                ->where('id', $product['product_id'])
                ->first();
                if (!empty($item)) {
                    $points += $item->points * $product['count'];
                }
            }
        }
        if ($request->receipt) {
            $orderRequest['receipt'] = $request->receipt;
        }
        else {
            $orderRequest['status'] = 1;

            $user->points += $points;
            $user->save();
        }
        $orderRequest['points'] = $points;
        $order = $this->order
        ->create($orderRequest);
        $user->address()->attach($request->address_id);
        $user->save();
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $key => $product) {
                $order_details[$key]['extras'] = [];
                $order_details[$key]['addons'] = [];
                $order_details[$key]['excludes'] = [];
                $order_details[$key]['product'] = [];
                $order_details[$key]['variations'] = [];

                $order_details[$key]['product'][] = [
                    'product' => $this->products
                    ->where('id', $product['product_id'])
                    ->first(),
                    'count' => $product['count']
                ];

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
                        
                        $order_details[$key]['excludes'][] = $this->excludes
                        ->where('id', $exclude)
                        ->first();
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
                        
                        $order_details[$key]['addons'][] = [
                            'addon' => $this->addons
                            ->where('id', $addon['addon_id'])
                            ->first(),
                            'count' => $addon['count']
                        ];
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
                        
                        $order_details[$key]['extras'][] = $this->extras
                        ->where('id', $extra)
                        ->first();
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
                        $order_details[$key]['variations'][] = [
                            'variation' => $this->variation
                            ->where('id', $variation['variation_id'])
                            ->first(),
                            'options' => $this->options
                            ->whereIn('id', $variation['option_id'])
                            ->get()
                        ];
                    }
                }
            }
        }
        $order->order_details = json_encode($order_details);
        $order->save();

        return response()->json([
            'success' => $order_details
        ]);
    }
}
