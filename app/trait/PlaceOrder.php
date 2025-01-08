<?php

namespace App\trait;

use App\Models\Payment; 
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait PlaceOrder
{ 
    // This Traite About Place Order
    protected $paymentRequest = [
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
    protected $orderRequest = ['user_id', 'cart'];
    protected $priceCycle;
    public function placeOrder($request, $user)
    {

        // Start Make Payment
        $paymentRequest = $request->only($this->paymentRequest);
        try {
            $activePaymentMethod = $this->paymentMethod->where('status', '1')->find($paymentRequest['payment_method_id']);
            if (!$activePaymentMethod) {
                return response()->json([
                    'paymentMethod.message' => 'This Payment Method Unavailable ',
                ], 404);
            }
            $order = $this->make_order($request, 1);
        } catch (\Throwable $th) {
            throw new HttpResponseException(response()->json(['error' => 'Payment processing failed'], 500));
        }
        // End Make Payment

        return [
            'payment' => $order['payment'],
            'orderItems' => $order['orderItems'],
            'items' => $order['items']
        ];
    }



    private function createOrdersForItems(array $items, string $field, array $baseData)
    {

        $createdOrders = [];
        $count = 1;
        foreach ($items as $item) {
            // Ensure $item is an array
            // return $items; 
            if (!is_array($item)) {
                throw new \InvalidArgumentException("Each item should be an array.");
            }
            $periodPrice = $item['price_cycle'];

            // Determine the model based on the $field
            $itemName = match ($field) {
                'extra_id' => 'extra',
                'domain_id' => 'domain',
                'plan_id' => 'plan',
                default => throw new \InvalidArgumentException("Invalid field provided: $field"),
            };
            $model = $this->$itemName->find($item[$field]);
            $this->priceCycle = $model->$periodPrice ?? $model->price;
            // Prepare the order data

            $orderData = array_merge($baseData, [
                $field => $item[$field],
                'price_cycle' => $periodPrice, // Add price_cycle here
                'price_item' => $this->priceCycle, // Add price_item here
            ]);

            // Validate if item has the field key
            if (!isset($item[$field])) {
                throw new \InvalidArgumentException("Missing $field key in item.");
            }
            // Create the order and retrieve the model
            $createdOrder = $this->order->create($orderData);
            // Prepare the item data
            $itemData = [
                'name' => $model->name,
                'amount_cents' => $this->priceCycle ?? $model->price,
                'period' => $item['price_cycle'],
                'quantity' => $count,
                'description' => "Your Item is $model->name and Price: " . $this->priceCycle ?? $model->price,
            ];

            $createdOrders[] = $itemData;
        }

        return $createdOrders;
    }



    public function payment_approve($payment)
    {
        if ($payment) {
            $payment->update(['status' => 1]);
            return true;
        }
        return false;
    }
    public function order_success($payment)
    {
    }

    public function make_order($request, $paymob = 0){
        $orderRequest = $request->only($this->paymentRequest); 
        $user = auth()->user();
        $orderRequest['user_id'] = $user->id;
        $orderRequest['order_status'] = 'pending';
        $points = 0;
        $items = [];
        $order_details = [];
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $product) {
                $item = $this->products
                ->where('id', $product['product_id'])
                ->first();
                if (!empty($item)) {
                    $items[] = [ "name"=> $item->name,
                            "amount_cents"=> $item->price,
                            "description"=> $item->description,
                            "quantity"=> $product['count']
                        ];
                    $points += $item->points * $product['count'];
                    if (isset($product['variation'])) {
                        foreach ($product['variation'] as $variation) {
                            if ($variation['option_id']) {
                                foreach ($variation['option_id'] as $option_id) {
                                    $option_points = $this->options
                                    ->where('id', $option_id)
                                    ->first()->points;
                                    $points += $option_points * $product['count'];
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($request->receipt) {
            $orderRequest['receipt'] = $request->receipt;
        }
        $orderRequest['points'] = $points;
        $order = $this->order
        ->create($orderRequest);
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
        if ($paymob) {
            $order->status = 2;
        }
        $order->save();

        return [
            'payment' => $order,
            'orderItems' => $order_details,
            'items' => $items
        ];
    }
}
