<?php

namespace App\trait;

use App\Models\Payment;
use App\Models\BranchOff;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

use App\Http\Resources\ProductResource;
use App\Http\Resources\AddonResource;
use App\Http\Resources\ExcludeResource;
use App\Http\Resources\ExtraResource;
use App\Http\Resources\VariationResource;
use App\Http\Resources\OptionResource;

trait POS
{ 
    // This Traite About Place Order
    protected $orderDataRequest = [
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
        'cashier_id', 
        'cashier_man_id', 
        'shift',
        'pos',
        'status',
        'cash_with_delivery',
        'balance',
        'user_id',
        'due',
        'dicount_id',
    ];

    public function take_away_make_order($request, $paymob = 0){
        $branch_off = BranchOff::
        where('branch_id', $request->branch_id)
        ->get();
        $products_off = $branch_off->pluck('product_id')->filter()->values()->all();
        $options_off = $branch_off->pluck('option_id')->filter()->values()->all();
        $categories_off = $branch_off->pluck('category_id')->filter()->values()->all();
        $orderRequest = $request->only($this->orderDataRequest); 
        $user = auth()->user();
      
        if (!empty($request->customer_id) && is_numeric($request->customer_id)) {
            $orderRequest['customer_id'] = $request->customer_id;
        }
        
        $orderRequest['order_status'] = 'pending';
        if ($request->table_id) {
            $orderRequest['table_id'] = $request->table_id;
        }
        if($request->order_pending){
            $orderRequest['order_active'] = 0;
        }
        
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
       // $points = 0;
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
                    // $points += $item->points * $product['count'];
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
                                   // $points += $option_points->points * $product['count'];
                                }
                            }
                        }
                    }
                }
            }
        } 
        // $orderRequest['points'] = $points;
        $order = $this->order
        ->create($orderRequest);
        // payment using financial
        if(isset($request->financials)){
            foreach ($request->financials as $element ) {
                $this->financial
                ->create([
                    'order_id' => $order->id,
                    'financial_id' => $element['id'],
                    'cashier_id' => $request->cashier_id,
                    'cashier_man_id' => $request->cashier_man_id,
                    'amount' => $element['amount'],
                    'description' => isset($element['description']) ? $element['description'] : null,
                    'transition_id' => isset($element['transition_id']) ? $element['transition_id'] : null,
                ]);
            }
        }
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $key => $product) {
                $amount_product = 0;
                $order_details[$key]['extras'] = [];
                $order_details[$key]['addons'] = [];
                $order_details[$key]['excludes'] = [];
                $order_details[$key]['product'] = [];
                $order_details[$key]['variations'] = [];

                $this->order_details
                ->create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'count' => $product['count'],
                    'product_index' => $key,
                ]);
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
                if (isset($product['exclude_id'])) {
                    foreach ($product['exclude_id'] as $exclude) {                       
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
            }
        } 
        $order->order_details = json_encode($order_details);
        $order->save();

        return [
            'order' => $order, 
        ];
    }


    public function dine_in_split_payment($request, $paymob = 0){
        $orderRequest = $request->only($this->orderDataRequest); 
        $user = auth()->user();
      
        // if (!empty($request->customer_id) && is_numeric($request->customer_id)) {
        //     $orderRequest['customer_id'] = $request->customer_id;
        // }
        
        $orderRequest['order_status'] = 'pending';
        // if ($request->table_id) {
        //     $orderRequest['table_id'] = $request->table_id;
        // } 
        
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
       // $points = 0;
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
                    // $points += $item->points * $product['count'];
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
                                   // $points += $option_points->points * $product['count'];
                                }
                            }
                        }
                    }
                }
            }
        } 
        // $orderRequest['points'] = $points;
        $order = $this->order
        ->create($orderRequest);
        // payment using financial
        if($request->financials && is_array($request->financials)){
            foreach ($request->financials as $element ) {
                $this->financial
                ->create([
                    'order_id' => $order->id,
                    'financial_id' => $element['id'],
                    'cashier_id' => $request->cashier_id,
                    'cashier_man_id' => $request->cashier_man_id,
                    'amount' => $element['amount'],
                    'description' => isset($element['description']) ? $element['description'] : null,
                    'transition_id' => isset($element['transition_id']) ? $element['transition_id'] : null,
                ]);
            }
        }
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $key => $product) {
                $amount_product = 0;
                $order_details[$key]['extras'] = [];
                $order_details[$key]['addons'] = [];
                $order_details[$key]['excludes'] = [];
                $order_details[$key]['product'] = [];
                $order_details[$key]['variations'] = [];

                $this->order_details
                ->create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'count' => $product['count'],
                    'product_index' => $key,
                ]);
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
                if (isset($product['exclude_id'])) {
                    foreach ($product['exclude_id'] as $exclude) {                       
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
            }
        } 
        $order->order_details = json_encode($order_details);
        $order->save();

        return [
            'order' => $order, 
        ];
    }

    public function delivery_make_order($request, $paymob = 0){
        $branch_off = BranchOff::
        where('branch_id', $request->branch_id)
        ->get();
        $products_off = $branch_off->pluck('product_id')->filter()->values()->all();
        $options_off = $branch_off->pluck('option_id')->filter()->values()->all();
        $categories_off = $branch_off->pluck('category_id')->filter()->values()->all();
        $orderRequest = $request->only($this->orderDataRequest); 
        $user = auth()->user();
      
        // if (!empty($request->customer_id) && is_numeric($request->customer_id)) {
        //     $orderRequest['customer_id'] = $request->customer_id;
        // }
        
        $orderRequest['order_status'] = 'pending';
        // if ($request->table_id) {
        //     $orderRequest['table_id'] = $request->table_id;
        // } 
        if($request->order_pending){
            $orderRequest['order_active'] = 0;
        }
        
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
       // $points = 0;
        $items = [];
        $order_details = [];
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $product) {
                $item = $this->products
                ->where('id', $product['product_id'] ?? null)
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
                            "quantity"=> $product['count'] ?? 0
                        ];
                    // $points += $item->points * $product['count'];
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
                                   // $points += $option_points->points * $product['count'];
                                }
                            }
                        }
                    }
                }
            }
        } 
        // $orderRequest['points'] = $points;
        $order = $this->order
        ->create($orderRequest);
        // payment using financial
        if($request->financials && is_array($request->financials)){
            foreach ($request->financials as $element ) {
                $this->financial
                ->create([
                    'order_id' => $order->id,
                    'financial_id' => $element['id'] ?? null,
                    'cashier_id' => $request->cashier_id,
                    'cashier_man_id' => $request->cashier_man_id,
                    'amount' => $element['amount'] ?? null,
                    'description' => isset($element['description']) ? $element['description'] : null,
                    'transition_id' => isset($element['transition_id']) ? $element['transition_id'] : null,
                ]);
            }
        }
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $key => $product) {
                $amount_product = 0;
                $order_details[$key]['extras'] = [];
                $order_details[$key]['addons'] = [];
                $order_details[$key]['excludes'] = [];
                $order_details[$key]['product'] = [];
                $order_details[$key]['variations'] = [];

                if(isset($request->order_pending) && !$request->order_pending){
                    $this->order_details
                    ->create([
                        'order_id' => $order->id,
                        'product_id' => $product['product_id'],
                        'count' => $product['count'],
                        'product_index' => $key,
                    ]);
                }
                $product_item = $this->products
                ->where('id', $product['product_id'] ?? null)
                ->withLocale($locale)
                ->first();
                $product_item = collect([$product_item]);
                $product_item = ProductResource::collection($product_item);
                $product_item = count($product_item) > 0 ? $product_item[0] : null;
                $order_details[$key]['product'][] = [
                    'product' => $product_item,
                    'count' => $product['count'] ?? null,
                    'notes' => isset($product['note']) ? $product['note'] : null,
                ];
                // Add product price
                $amount_product += $product_item->price ?? 0; 
                if (isset($product['exclude_id'])) {
                    foreach ($product['exclude_id'] as $exclude) {                       
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
            }
        } 
        $order->order_details = json_encode($order_details);
        $order->save();

        return [
            'order' => $order, 
        ];
    }

    public function dine_in_make_order($request, $paymob = 0){
        $branch_off = BranchOff::
        where('branch_id', $request->branch_id)
        ->get();
        $products_off = $branch_off->pluck('product_id')->filter()->values()->all();
        $options_off = $branch_off->pluck('option_id')->filter()->values()->all();
        $categories_off = $branch_off->pluck('category_id')->filter()->values()->all();
        $orderRequest = $request->only($this->orderDataRequest); 
        $user = auth()->user();
      
        if (!empty($request->customer_id) && is_numeric($request->customer_id)) {
            $orderRequest['customer_id'] = $request->customer_id;
        }
        
        $orderRequest['order_status'] = 'pending';
        if ($request->table_id) {
            $orderRequest['table_id'] = $request->table_id;
        } 
        
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
       // $points = 0;
        $items = [];
        $order_details = [];
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $product) {
                $item = $this->products
                ->where('id', $product['product_id'])
                ->first();
                if (!empty($item)) {
                    if (in_array($item->id, $products_off) || 
                    in_array($item->category_id, $categories_off) ||
                    in_array($item->sub_category_id, $categories_off)) {
                        return [
                            'errors' => 'Product ' . $item->name . 
                            ' is not found at this branch you can change branch or order'
                        ];
                    }
                    $items[] = [ "name"=> $item->name,
                            "amount_cents"=> $item->price,
                            "description"=> $item->description,
                            "quantity"=> $product['count']
                        ];
                    // $points += $item->points * $product['count'];
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
                                   // $points += $option_points->points * $product['count'];
                                }
                            }
                        }
                    }
                }
            }
        } 
        // $orderRequest['points'] = $points;
        $order = $this->order
        ->create($orderRequest);
        // payment using financial
        foreach ($request->financials as $element ) {
            $this->financial
            ->create([
                'order_id' => $order->id,
                'financial_id' => $element['id'],
                'cashier_id' => $request->cashier_id,
                'cashier_man_id' => $request->cashier_man_id,
                'amount' => $element['amount'],
                'description' => isset($element['description']) ? $element['description'] : null,
                'transition_id' => isset($element['transition_id']) ? $element['transition_id'] : null,
            ]);
        }
        if (isset($request->products)) {
            $request->products = is_string($request->products) ? json_decode($request->products) : $request->products;
            foreach ($request->products as $key => $product) {
                $amount_product = 0;
                $order_details[$key]['extras'] = [];
                $order_details[$key]['addons'] = [];
                $order_details[$key]['excludes'] = [];
                $order_details[$key]['product'] = [];
                $order_details[$key]['variations'] = [];

                $this->order_details
                ->create([
                    'order_id' => $order->id,
                    'product_id' => $product['product_id'],
                    'count' => $product['count'],
                    'product_index' => $key,
                ]);
                $product_item = $this->products
                ->where('id', $product['product_id'])
                ->withLocale($locale)
                ->first();
                if($product_item){
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
                }
                if (isset($product['exclude_id'])) {
                    foreach ($product['exclude_id'] as $exclude) {                       
                        $exclude = $this->excludes
                        ->where('id', $exclude)
                        ->withLocale($locale)
                        ->first();
                        if($exclude){
                            $exclude = collect([$exclude]);
                            $exclude = ExcludeResource::collection($exclude);
                            $exclude = count($exclude) > 0 ? $exclude[0] : null;
                            $order_details[$key]['excludes'][] = $exclude;
                        }
                    }
                } 
                if (isset($product['addons'])) {
                    foreach ($product['addons'] as $addon) {
                        
                        $addon_item = $this->addons
                        ->where('id', $addon['addon_id'])
                        ->withLocale($locale)
                        ->first();
                        if($addon_item){
                            $addon_item = collect([$addon_item]);
                            $addon_item = AddonResource::collection($addon_item);
                            $addon_item = count($addon_item) > 0 ? $addon_item[0] : null;
                            $order_details[$key]['addons'][] = [
                                'addon' => $addon_item,
                                'count' => $addon['count']
                            ]; 
                        }
                    }
                } 
                if (isset($product['extra_id'])) {
                    foreach ($product['extra_id'] as $extra) {
                        $extra_item = $this->extras
                        ->where('id', $extra)
                        ->withLocale($locale)
                        ->first();
                        if($extra_item){
                            $extra_item = collect([$extra_item]);
                            $extra_item = ExtraResource::collection($extra_item);
                            $extra_item = count($extra_item) > 0 ? $extra_item[0] : null;
                            $order_details[$key]['extras'][] = $extra_item; 
                        }
                    }
                }
                if (isset($product['product_extra_id'])) {
                    foreach ($product['product_extra_id'] as $extra) {
                        $extra_item = $this->extras
                        ->where('id', $extra)
                        ->withLocale($locale)
                        ->first();
                        if($extra_item){
                            $extra_item = collect([$extra_item]);
                            $extra_item = ExtraResource::collection($extra_item);
                            $extra_item = count($extra_item) > 0 ? $extra_item[0] : null;
                            $order_details[$key]['extras'][] = $extra_item; 
                        }
                    }
                }
                if (isset($product['variation'])) {
                    foreach ($product['variation'] as $variation) {
                        $variation_items = $this->variation
                        ->where('id', $variation['variation_id'])
                        ->withLocale($locale)
                        ->first();
                        $variations = collect([$variation_items]);
                        $options = $this->options
                        ->whereIn('id', $variation['option_id'])
                        ->withLocale($locale)
                        ->get();
                        if($variation_items && count($options) > 0){
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
                } 
            }
            $order->order_details = json_encode($order_details);
            $order->save();
        } 

        return [
            'payment' => $order, 
        ];
    }
}
