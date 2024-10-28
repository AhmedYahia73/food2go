<?php

namespace App\Http\Controllers\api\customer\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\order\OrderRequest;

use App\Models\Order;
use App\Models\OrderDetails;

class MakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetails $order_details){}
    protected $orderRequest = [
        'date',
        'branch_id',
        'amount',
        'payment_status',
        'total_tax',
        'total_discount',
        'address',
        'order_type',
        'paid_by',
    ];

    public function order(OrderRequest $request){
        // Keys
        // date, branch_id, amount, payment_status, total_tax, total_discount, address
        // order_type, paid_by
        // products[{product_id, exclude_id[], extra_id[], variation[{variation_id, option_id[]}], count}]
        $orderRequest = $request->only($this->orderRequest);
        $user = $request->user();
        $orderRequest['user_id'] = $user->id;
        $orderRequest['order_status'] = 'pending';
        $order = $this->order
        ->create($orderRequest);        
        $user->address = $request->address && is_string($request->address) 
        ? $request->address : json_encode($request->address);
        foreach ($request->products as $key => $product) {
            for ($i=0, $end = count($product->count); $i < $end; $i++) { 
                $order->products()->attach($product->product_id);
            }
            $order->sales_count()->attach($product->product_id, [
                'count' => $product->count
            ]);
            $this->order_details
            ->create([
                'order_id' => $order->id,
                'product_id' => $product->product_id,
                'count' => $product->count,
                'product_num' => $key,
            ]); // Add product with count
            foreach ($product->exclude_id as $exclude) {
                $this->order_details
                ->create([
                    'order_id' => $order->id,
                    'product_id' => $product->product_id,
                    'exclude_id' => $exclude,
                    'count' => $product->count,
                    'product_num' => $key,
                ]); // Add excludes
            }
            foreach ($product->extra_id as $extra) {
                $this->order_details
                ->create([
                    'order_id' => $order->id,
                    'product_id' => $product->product_id,
                    'extra_id' => $extra,
                    'count' => $product->count,
                    'product_num' => $key,
                ]); // Add extra
            }
            foreach ($product->variation as $variation) {
                foreach ($variation->option_id as $option_id) {
                    $this->order_details
                    ->create([
                        'order_id' => $order->id,
                        'product_id' => $product->product_id,
                        'variation_id' => $variation->variation_id,
                        'option_id' => $option_id,
                        'count' => $product->count,
                        'product_num' => $key,
                    ]); // Add variations & options
                }
            }
        }
        $user->save();

        return response()->json([
            'success' => 'You make order success'
        ]);
    }
}
