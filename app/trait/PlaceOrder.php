<?php

namespace App\service\order;

use App\Models\Payment;
use App\service\ExpireDate;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait placeOrder
{
    use ExpireDate;
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
    public function placeOrder($request, $user, $orderType)
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
    
        } catch (\Throwable $th) {
            throw new HttpResponseException(response()->json(['error' => 'Payment processing failed'], 500));
        }
        // End Make Payment

        return [
            'payment' => $order,
            'orderItems' => $order_details,
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
            $payment->update(['status' => 'approved']);
            return true;
        }
        return false;
    }
    public function order_success($payment)
    {
        $payment_approved = $this->payment_approve($payment);
        // Retrieve orders related to the payment
        $orders = $payment->orders;
        $user = $payment->user;
        // Collect unique IDs for batch fetching
        $domainIds = $orders->whereNotNull('domain_id')->pluck('domain_id', 'price_cycle')->unique();
        $extraIds = $orders->whereNotNull('extra_id')->pluck('extra_id', 'price_cycle')->unique();
        $planIds = $orders->whereNotNull('plan_id')->pluck('plan_id')->unique();
        $plan_price_cycle = $orders->whereNotNull('price_cycle')->pluck('price_cycle')->unique();
        // Approved Domains
        if ($domainIds->isNotEmpty()) {
            $this->domain->whereIn('id', $domainIds)->update(['price_status' => true]);
        }
        if ($planIds->isNotEmpty()) {
            $expireDate = $this->getExpireDateTime($plan_price_cycle, now());
            $packate_cycle = $this->package_cycle($plan_price_cycle, now());
           foreach ($planIds as $key => $plan_id) {
                 $user->update([
                 'plan_id' => $plan_id,
                 'expire_date' => $expireDate,
                 'package' => $packate_cycle,
                 ]);
           }
        }
        // Update Order And Put Expire Date
    foreach($orders as $order){
            $priceCycle = $order->price_cycle ;
            $expireDate = $this->getOrderDateExpire($priceCycle,now());
            $order->update(['expire_date'=>$expireDate]);
    }

    
        // End Approved Domains   
        // Fetch all required services in batch only if IDs are present
        $domains = $domainIds->isNotEmpty() ? $this->domain->whereIn('id', $domainIds)->get()->keyBy('id') : collect();
        $extras = $extraIds->isNotEmpty() ? $this->extra->whereIn('id', $extraIds)->get()->keyBy('id') : collect();
        $plans = $planIds->isNotEmpty() ? $this->plan->whereIn('id', $planIds)->get()->keyBy('id') :
            collect();
        $createdOrders = $orders->map(function ($order) use ($domains, $extras, $plans) {
            $newService = [];

            if ($order->domain_id !== null) {
                $newService['domain'] = $domains->find($order->domain_id);
            }
            if ($order->extra_id !== null) {
                $newService['extra'] = $extras->find($order->extra_id);
            }
            if ($order->plan_id !== null) {
                $newService['plan'] = $plans->find($order->plan_id);
            }

            return $newService;
        });

        return $orders;
    }

    public function make_order($paymentRequest){
        $user = auth()->user();
        $paymentRequest['user_id'] = $user->id;
        $paymentRequest['order_status'] = 'pending';
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

        return $order;
    }
}
