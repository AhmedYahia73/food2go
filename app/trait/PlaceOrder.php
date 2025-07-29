<?php

namespace App\trait;

use App\Models\Payment;
use App\Models\BranchOff;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Http\Resources\ProductResource;
use App\Http\Resources\AddonResource;
use App\Http\Resources\ExcludeResource;
use App\Http\Resources\ExtraResource;
use App\Http\Resources\VariationResource;
use App\Http\Resources\OptionResource;

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
        'branch_id',
        'order_type',
        'payment_method_id',
        'notes',
        'coupon_discount',
        'sechedule_slot_id',
        'source',
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
            if (isset($order['errors']) && !empty($order['errors'])) {
                return $order;
            }
        } catch (\Throwable $th) {
            throw new HttpResponseException(response()->json(['errors' => 'Payment processing failed'], 500));
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
        $branch_off = BranchOff::
        where('branch_id', $request->branch_id)
        ->get();
        $products_off = $branch_off->pluck('product_id')->filter()->values()->all();
        $options_off = $branch_off->pluck('option_id')->filter()->values()->all();
        $categories_off = $branch_off->pluck('category_id')->filter()->values()->all();
        $orderRequest = $request->only($this->paymentRequest); 
        $user = auth()->user();
        if (!empty($request->user_id) && is_numeric($request->user_id)) {
            $orderRequest['user_id'] = $request->user_id;
        }
        elseif(!$request->user_id){
            $orderRequest['user_id'] = $user->id;
        }
        if (!empty($request->user_id) && is_numeric($request->user_id)) {
            $orderRequest['user_id'] = $request->user_id;
        }
        if (!empty($request->customer_id) && is_numeric($request->customer_id)) {
            $orderRequest['customer_id'] = $request->customer_id;
        }
        
        $orderRequest['order_status'] = 'pending';
        if ($request->table_id) {
            $orderRequest['table_id'] = $request->table_id;
        }
        if ($request->captain_id) {
            $orderRequest['captain_id'] = $request->captain_id;
        }
        if ($request->cashier_id) {
            $orderRequest['cashier_id'] = $request->cashier_id;
        }
        if ($request->cashier_man_id) {
            $orderRequest['cashier_man_id'] = $request->cashier_man_id;
        }
        if ($request->shift) { 
            $orderRequest['shift'] = $request->shift;
        }
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $points = 0;
        $items = [];
        $order_details = [];
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $product) {
                $item = $this->products
                ->where('id', $product['product_id'])
                ->first();
                if (in_array($item->id, $products_off) || 
                in_array($item->category_id, $categories_off) ||
                in_array($item->sub_category_id, $categories_off)) {
                    return [
                        'errors' => 'Product ' . $item->name . 
                        ' is not found at this branch you can change branch or order'
                    ];
                }
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
                                    ->first();
                                    if (in_array($option_points->id, $options_off)) {
                                        return [
                                            'errors' => 'Option ' . $option_points->name . ' at product ' . $item->name . 
                                            ' is not found at this branch you can change branch or order'
                                        ];
                                    }
                                    $points += $option_points->points * $product['count'];
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
                $amount_product = 0;
                $order_details[$key]['extras'] = [];
                $order_details[$key]['addons'] = [];
                $order_details[$key]['excludes'] = [];
                $order_details[$key]['product'] = [];
                $order_details[$key]['variations'] = [];

                $product_item = $this->products
                ->where('id', $product['product_id'])
                ->withLocale($locale)
                ->first();
                $product_item = collect([$product_item]);
                $product_item = ProductResource::collection($product_item);
                $product_item = count($product_item) > 0 ? $product_item[0] : null;
                $order_details[$key]['product'][] = [
                    'product' => $product_item,
                    'count' => $product['count'],
                    'notes' => isset($product['note']) ? $product['note'] : null,
                ];
                // Add product price
                $amount_product += $product_item->price;

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
                        
                        $exclude = $this->excludes
                        ->where('id', $exclude)
                        ->withLocale($locale)
                        ->first();
                        $exclude = collect([$exclude]);
                        $exclude = ExcludeResource::collection($exclude);
                        $exclude = count($exclude) > 0 ? $exclude[0] : null;
                        $order_details[$key]['excludes'][] = $exclude;
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
                        
                        $addon_item = $this->addons
                        ->where('id', $addon['addon_id'])
                        ->withLocale($locale)
                        ->first();
                        $addon_item = collect([$addon_item]);
                        $addon_item = AddonResource::collection($addon_item);
                        $addon_item = count($addon_item) > 0 ? $addon_item[0] : null;
                        $order_details[$key]['addons'][] = [
                            'addon' => $addon_item,
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
                        $extra_item = $this->extras
                        ->where('id', $extra)
                        ->withLocale($locale)
                        ->first();
                        $extra_item = collect([$extra_item]);
                        $extra_item = ExtraResource::collection($extra_item);
                        $extra_item = count($extra_item) > 0 ? $extra_item[0] : null;
                        $order_details[$key]['extras'][] = $extra_item; 
                    }
                }
                if (isset($product['product_extra_id'])) {
                    foreach ($product['product_extra_id'] as $extra) {
                        $this->order_details
                        ->create([
                            'order_id' => $order->id,
                            'product_id' => $product['product_id'],
                            'extra_id' => $extra,
                            'count' => $product['count'],
                            'product_index' => $key,
                        ]); // Add extra
                        
                        $extra_item = $this->extras
                        ->where('id', $extra)
                        ->withLocale($locale)
                        ->first();
                        $extra_item = collect([$extra_item]);
                        $extra_item = ExtraResource::collection($extra_item);
                        $extra_item = count($extra_item) > 0 ? $extra_item[0] : null;
                        $order_details[$key]['extras'][] = $extra_item; 
                    }
                }
                if (isset($product['variation'])) {
					$product['variation'] = collect($product['variation'])->unique('variation_id');
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
                        $variations = $this->variation
                        ->where('id', $variation['variation_id'])
                        ->withLocale($locale)
                        ->first();
                        $variations = collect([$variations]);
                        $options = $this->options
                        ->whereIn('id', $variation['option_id'])
                        ->withLocale($locale)
                        ->get();
                        $variations = VariationResource::collection($variations);
                        $variations = count($variations) > 0 ? $variations[0] : null;
                        $options = OptionResource::collection($options);
                        $order_details[$key]['variations'][] = [
                            'variation' => $variations,
                            'options' => $options,
                        ];
                        $amount_product += $this->options
                        ->whereIn('id', $variation['option_id'])
                        ->sum('price');
                    }
                }
                $discount_item = $product_item->discount;
                $tax_item = $product_item->tax;
                $tax = $this->settings
                ->where('name', 'tax')
                ->orderByDesc('id')
                ->first();
                if (!empty($tax_item)) {
                    if (!empty($tax)) {
                        $tax = $tax->setting;
                    }
                    else {
                        $tax = $this->settings
                        ->create([
                            'name' => 'tax',
                            'setting' => 'included',
                        ]);
                        $tax = $tax->setting;
                    }
                    if ($tax_item->type == 'precentage') { 
                        $amount_product = $amount_product + $amount_product * $tax_item->amount / 100;
                    }
                    else{ 
                        $amount_product = $amount_product + $tax_item->amount;
                    }
                }
                if (!empty($discount_item)) {
                    if ($discount_item->type == 'precentage') { 
                        $amount_product = $amount_product - $amount_product * $discount_item->amount / 100;
                    }
                    else{ 
                        $amount_product = $amount_product - $discount_item->amount;
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
            'items' => $items,
        ];
    }

    public function make_order_cart($request, $paymob = 0){
        $branch_off = BranchOff::
        where('branch_id', $request->branch_id)
        ->get();
        $products_off = $branch_off->pluck('product_id')->filter()->values()->all();
        $options_off = $branch_off->pluck('option_id')->filter()->values()->all();
        $categories_off = $branch_off->pluck('category_id')->filter()->values()->all();
        $orderRequest = $request->only($this->paymentRequest); 
        $user = auth()->user();
        
        if ($request->table_id) {
            $orderRequest['table_id'] = $request->table_id;
        }
        if ($request->captain_id) {
            $orderRequest['captain_id'] = $request->captain_id;
        }
        if ($request->cashier_id) {
            $orderRequest['cashier_id'] = $request->cashier_id;
        }
        if ($request->cashier_man_id) {
            $orderRequest['cashier_man_id'] = $request->cashier_man_id;
        }
        if ($request->shift) { 
            $orderRequest['shift'] = $request->shift;
        }
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $points = 0;
        $items = [];
        $order_details = [];
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $product) {
                $item = $this->products
                ->where('id', $product['product_id'])
                ->first();
                if (in_array($item->id, $products_off) || 
                in_array($item->category_id, $categories_off) ||
                in_array($item->sub_category_id, $categories_off)) {
                    return [
                        'errors' => 'Product ' . $item->name . 
                        ' is not found at this branch you can change branch or order'
                    ];
                }
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
                                    ->first();
                                    if (in_array($option_points->id, $options_off)) {
                                        return [
                                            'errors' => 'Option ' . $option_points->name . ' at product ' . $item->name . 
                                            ' is not found at this branch you can change branch or order'
                                        ];
                                    }
                                    $points += $option_points->points * $product['count'];
                                }
                            }
                        }
                    }
                }
            }
        }
        $orderRequest['points'] = $points;
        $order = $this->order_cart
        ->create($orderRequest);
        $user->save();
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $key => $product) {
                $amount_product = 0;
                $order_details[$key]['extras'] = [];
                $order_details[$key]['addons'] = [];
                $order_details[$key]['excludes'] = [];
                $order_details[$key]['product'] = [];
                $order_details[$key]['variations'] = [];

                $product_item = $this->products
                ->where('id', $product['product_id'])
                ->withLocale($locale)
                ->first();
                $product_item = collect([$product_item]);
                $product_item = ProductResource::collection($product_item);
                $product_item = count($product_item) > 0 ? $product_item[0] : null; 
                $order_details[$key]['product'][] = [
                    'product' => $product_item,
                    'count' => $product['count'],
                    'prepration' => 'watting',
                    'notes' => isset($product['note']) ? $product['note'] : null,
                ];
                // Add product price
                $amount_product += $product_item->price;

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
                        
                        $exclude = $this->excludes
                        ->where('id', $exclude)
                        ->withLocale($locale)
                        ->first();
                        $exclude = collect([$exclude]);
                        $exclude = ExcludeResource::collection($exclude);
                        $exclude = count($exclude) > 0 ? $exclude[0] : null;
                        $order_details[$key]['excludes'][] = $exclude;
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
                        
                        $addon_item = $this->addons
                        ->where('id', $addon['addon_id'])
                        ->withLocale($locale)
                        ->first();
                        $addon_item = collect([$addon_item]);
                        $addon_item = AddonResource::collection($addon_item);
                        $addon_item = count($addon_item) > 0 ? $addon_item[0] : null; 
                        $order_details[$key]['addons'][] = [
                            'addon' => $addon_item,
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
                        $extra_item = $this->extras
                        ->where('id', $extra)
                        ->withLocale($locale)
                        ->first();
                        $extra_item = collect([$extra_item]);
                        $extra_item = ExtraResource::collection($extra_item);
                        $extra_item = count($extra_item) > 0 ? $extra_item[0] : null; 
                        $order_details[$key]['extras'][] = $extra_item; 
                    }
                }
                if (isset($product['product_extra_id'])) {
                    foreach ($product['product_extra_id'] as $extra) {
                        $this->order_details
                        ->create([
                            'order_id' => $order->id,
                            'product_id' => $product['product_id'],
                            'extra_id' => $extra,
                            'count' => $product['count'],
                            'product_index' => $key,
                        ]); // Add extra
                        
                        $extra_item = $this->extras
                        ->where('id', $extra)
                        ->withLocale($locale)
                        ->first();
                        $extra_item = collect([$extra_item]);
                        $extra_item = ExtraResource::collection($extra_item);
                        $extra_item = count($extra_item) > 0 ? $extra_item[0] : null; 
                        $order_details[$key]['extras'][] = $extra_item;  
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
                        $variations = $this->variation
                        ->where('id', $variation['variation_id'])
                        ->withLocale($locale)
                        ->first();
                        $variations = collect([$variations]);
                        $options = $this->options
                        ->whereIn('id', $variation['option_id'])
                        ->withLocale($locale)
                        ->get();
                        $variations = VariationResource::collection($variations);
                        $variations = count($variations) > 0 ? $variations[0] : null;
                        $options = OptionResource::collection($options);
                        $order_details[$key]['variations'][] = [
                            'variation' => $variations,
                            'options' => $options,
                        ];
                        // $order_details[$key]['excludes'] = [];
                        // $order_details[$key]['variations'] = [];
                        $amount_product += $this->options
                        ->whereIn('id', $variation['option_id'])
                        ->sum('price');
                    }
                }
                $discount_item = $product_item->discount;
                $tax_item = $product_item->tax;
                $tax = $this->settings
                ->where('name', 'tax')
                ->orderByDesc('id')
                ->first();
                if (!empty($tax_item)) {
                    if (!empty($tax)) {
                        $tax = $tax->setting;
                    }
                    else {
                        $tax = $this->settings
                        ->create([
                            'name' => 'tax',
                            'setting' => 'included',
                        ]);
                        $tax = $tax->setting;
                    }
                    if ($tax_item->type == 'precentage') { 
                        $amount_product = $amount_product + $amount_product * $tax_item->amount / 100;
                    }
                    else{ 
                        $amount_product = $amount_product + $tax_item->amount;
                    }
                }
                if (!empty($discount_item)) {
                    if ($discount_item->type == 'precentage') { 
                        $amount_product = $amount_product - $amount_product * $discount_item->amount / 100;
                    }
                    else{ 
                        $amount_product = $amount_product - $discount_item->amount;
                    }
                } 
            }
        } 
        $order->cart = json_encode($order_details);
        $order->save();

        return [
            'payment' => $order,
            'orderItems' => $order_details,
            'items' => $items,
        ];
    }

    public function order_format($order, $key = 0){
        $order_data = [];
        foreach ($order->cart ?? $order as $key => $item) {
            $product = $item->product[0]->product;
            unset($product->addons);
            unset($product->variations);
            $variation = [];
            $addons = [];
            // $item->addons->addon->count = $item->addons->count;
            // $item->variations->variation->options = $item->variations->options;
            foreach ($item->variations as $key => $element) {
                $element->variation->options = $element->options;
                unset($element->options);
                $variation[] = $element->variation;
            }
            foreach ($item->addons as $key => $element) {
                $element->addon->count = $element->count;
                unset($element->count);
                $addons[] = $element->addon;
            }
            $order_data[$key] = $product;
            $order_data[$key]->cart_id = $order->id;
            $order_data[$key]->product_index = $key;
            $order_data[$key]->count = $item->product[0]->count;
            $order_data[$key]->prepration = $order->prepration_status ?? $item->product[0]->prepration;
            $order_data[$key]->excludes = $item->excludes;
            $order_data[$key]->extras = $item->extras;
            $order_data[$key]->variation_selected = $variation;
            $order_data[$key]->addons_selected = $addons;
        }

        return $order_data;
    }

    public function takeaway_order_format($order){
        $order_data = [];
        foreach ($order->order_details ?? $order as $key => $item) {
            $product = $item->product[0]->product;
            unset($product->addons);
            unset($product->variations);
            $variation = [];
            $addons = [];
            // $item->addons->addon->count = $item->addons->count;
            // $item->variations->variation->options = $item->variations->options;
            foreach ($item->variations as $key => $element) {
                $element->variation->options = $element->options;
                unset($element->options);
                $variation[] = $element->variation;
            }
            foreach ($item->addons as $key => $element) {
                $element->addon->count = $element->count;
                unset($element->count);
                $addons[] = $element->addon;
            }
            $order_data[$key] = $product;
            $order_data[$key]->cart_id = $order->id; 
            $order_data[$key]->count = $item->product[0]->count; 
            $order_data[$key]->excludes = $item->excludes;
            $order_data[$key]->extras = $item->extras;
            $order_data[$key]->variation_selected = $variation;
            $order_data[$key]->addons_selected = $addons;
        }

        return $order_data;
    }
}
