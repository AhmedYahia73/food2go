<?php

namespace App\trait;

use App\Models\Order; 
use App\Models\TranslationTbl; 
 
use Illuminate\Http\Request;

trait OrderFormat
{ 
    public function order_details_format($id, $locale){
        $order = Order::
        with(['user', 'address.zone.city', 'admin:id,name,email,phone,image', 
        'branch', 'delivery'])
        ->where("id", $id)
        ->first();
        if(empty($order->order_details_data)){
            return null;
        }
        $products = [];
        foreach ($order->order_details_data as $item) {
            # TranslationTbl
            $extras = [];
            $addons = [];
            $excludes = [];
            $variations = [];
            $product = [];
            foreach ($item['extras'] as $element) {
                $name = TranslationTbl::
                where("key", $element['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['name'];
                $extras[] = [
                    "id" => $element['id'],
                    "name" => $name,
                    "price" => $element['price'],
                ];
            }
            foreach ($item['addons'] as $element) {
                $name = TranslationTbl::
                where("key", $element['addon']['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['addon']['name'];
                $addons[] = [
                    "id" => $element['addon']['id'],
                    "name" => $name,
                    "price" => $element['addon']['price'],
                ];
            }
            foreach ($item['excludes'] as $element) {
                $name = TranslationTbl::
                where("key", $element['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['name'];
                $excludes[] = [
                    "id" => $element['id'],
                    "name" => $name,
                ];
            }
            foreach ($item['variations'] as $element) {
                $name = TranslationTbl::
                where("key", $element['variation']['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['variation']['name'];
                $options = [];
                foreach ($element['options'] as $value) {
                    $option_name = TranslationTbl::
                    where("key", $value['name'])
                    ->where("locale", $locale)
                    ->orderByDesc("id")
                    ->first()?->value ?? $value['name'];
                    $options[] = [
                        "id" => $value['id'],
                        "name" => $option_name,
                        "price" => $value['price'],
                        "total_option_price" => $value['total_option_price'],
                    ];
                }
                $variations[] = [
                    "id" => $element['variation']['id'],
                    "name" => $name,
                    //"price" => $element['variation']['price'],
                    "options" => $options,
                ];
            }
            if(isset($item['product'][0]['product'])){
                $name = TranslationTbl::
                where("key", $item['product'][0]['product']['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $item['product'][0]['product']['name'];
                $product = [
                    'id' => $item['product'][0]['product']['id'],
                    'name' => $name,
                    'price' => $item['product'][0]['product']['price'],
                    'price_after_discount' => $item['product'][0]['product']['price_after_discount'],
                    'price_after_tax' => $item['product'][0]['product']['price_after_tax'],
                    'count' => $item['product'][0]['count'],
                    'notes' => $item['product'][0]['notes'],
                ];
            }  
            $product_item = [
                "extras" => $extras,
                "addons" => $addons,
                "addons" => $addons,
                "variations" => $variations,
                "product" => $product,
            ];
            $products[] = $product_item;
        }
        $order_arr = [
            "id" => $order->id,
            "order_details" => $products,
            "amount" => $order->amount,
            "rate" => $order->rate,
            "comment" => $order->comment,
            "order_status" => $order->order_status,
            "payment" => $order->payment_method_id !== 2 ? "Paid" : "UnPaid",
            "order_type" => $order->order_type,
            "total_tax" => $order->total_tax,
            "total_discount" => $order->total_discount,
            "notes" => $order->notes,
            "coupon_discount" => $order->coupon_discount,
            "order_number" => $order->order_number,
            "rejected_reason" => $order->rejected_reason,
            "transaction_id" => $order->transaction_id,
            "customer_cancel_reason" => $order->customer_cancel_reason,
            "source" => $order->source,
            "order_date" => $order->order_date,
            "status_payment" => $order->status_payment,
            "branch" => [
                "id" => $order?->branch?->id ?? null,
                "name" => $order?->branch
                ?->translations()
                ?->where("key", $order?->branch?->name ?? null)
                ?->where("locale", $locale)
                ?->first()
                ?->value ?? $order?->branch?->name ?? null,
                "address" => $order?->branch?->address ?? null,
                "email" => $order?->branch?->email ?? null,
                "phone" => $order?->branch?->phone ?? null,
            ],
            "user" => [
                "id" => $order?->user?->id ?? null,
                "f_name" => $order?->user?->f_name ?? null,
                "l_name" => $order?->user?->l_name ?? null,
                "email" => $order?->user?->email ?? null,
                "phone" => $order?->user?->phone ?? null,
                "phone_2" => $order?->user?->phone_2 ?? null,
                "name" => $order?->user?->name ?? null,
            ],
            "admin" => [
                "id" => $order?->admin?->id ?? null,
                "name" => $order?->admin?->name ?? null,
            ],
            "delivery" => [
                "id" => $order?->delivery?->id ?? null,
                "f_name" => $order?->delivery?->f_name ?? null,
                "l_name" => $order?->delivery?->l_name ?? null,
                "phone" => $order?->delivery?->phone ?? null,
            ],
            "address" => [
                "id" => $order?->address?->id ?? null,
                "address" => $order?->address?->address ?? null,
                "street" => $order?->address?->street ?? null,
                "building_num" => $order?->address?->building_num ?? null,
                "floor_num" => $order?->address?->floor_num ?? null,
                "apartment" => $order?->address?->apartment ?? null,
                "additional_data" => $order?->address?->additional_data ?? null,
                "type" => $order?->address?->type ?? null,
                "map" => $order?->address?->map ?? null,
                "zone" => [
                    "zone" => $order?->address?->zone?->zone ?? null,
                    "price" => $order?->address?->zone?->price ?? null,
                ],
                "city" => $order?->address?->zone?->city?->name ?? null,
            ],  
        ];
        
        return $order_arr;
    }

    // ___________________________________________________________

    public function order_item_format($order, $id, $locale){
        if(empty($order->order_details_data)){
            return null;
        }
        $products = [];
        foreach ($order->order_details_data as $item) {
            # TranslationTbl
            $extras = [];
            $addons = [];
            $excludes = [];
            $variations = [];
            $product = [];
            foreach ($item['extras'] as $element) {
                $name = TranslationTbl::
                where("key", $element['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['name'];
                $extras[] = [
                    "id" => $element['id'],
                    "name" => $name,
                    "price" => $element['price'],
                ];
            }
            foreach ($item['addons'] as $element) {
                $name = TranslationTbl::
                where("key", $element['addon']['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['addon']['name'];
                $addons[] = [
                    "id" => $element['addon']['id'],
                    "name" => $name,
                    "price" => $element['addon']['price'],
                ];
            }
            foreach ($item['excludes'] as $element) {
                $name = TranslationTbl::
                where("key", $element['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['name'];
                $excludes[] = [
                    "id" => $element['id'],
                    "name" => $name,
                ];
            }
            foreach ($item['variations'] as $element) {
                $name = TranslationTbl::
                where("key", $element['variation']['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['variation']['name'];
                $options = [];
                foreach ($element['options'] as $value) {
                    $option_name = TranslationTbl::
                    where("key", $value['name'])
                    ->where("locale", $locale)
                    ->orderByDesc("id")
                    ->first()?->value ?? $value['name'];
                    $options[] = [
                        "id" => $value['id'],
                        "name" => $option_name,
                        "price" => $value['price'],
                        "total_option_price" => $value['total_option_price'],
                    ];
                }
                $variations[] = [
                    "id" => $element['variation']['id'],
                    "name" => $name,
                    //"price" => $element['variation']['price'],
                    "options" => $options,
                ];
            }
            if(isset($item['product'][0]['product'])){
                $name = TranslationTbl::
                where("key", $item['product'][0]['product']['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $item['product'][0]['product']['name'];
                $product = [
                    'id' => $item['product'][0]['product']['id'],
                    'name' => $name,
                    'price' => $item['product'][0]['product']['price'],
                    'price_after_discount' => $item['product'][0]['product']['price_after_discount'],
                    'price_after_tax' => $item['product'][0]['product']['price_after_tax'],
                    'count' => $item['product'][0]['count'],
                    'notes' => $item['product'][0]['notes'],
                ];
            }  
            $product_item = [
                "extras" => $extras,
                "addons" => $addons,
                "addons" => $addons,
                "variations" => $variations,
                "product" => $product,
            ];
            $products[] = $product_item;
        }
        $order_arr = [
            "id" => $order->id,
            "order_details" => $products,
            "amount" => $order->amount,
            "order_status" => $order->order_status,
            "payment" => $order->payment_method_id !== 2 ? "Paid" : "UnPaid",
            "order_type" => $order->order_type,
            "total_tax" => $order->total_tax,
            "total_discount" => $order->total_discount,
            "notes" => $order->notes,
            "coupon_discount" => $order->coupon_discount,
            "order_number" => $order->order_number,
            "rejected_reason" => $order->rejected_reason,
            "transaction_id" => $order->transaction_id,
            "customer_cancel_reason" => $order->customer_cancel_reason,
            "source" => $order->source,
            "order_date" => $order->order_date,
            "status_payment" => $order->status_payment,
            "branch" => [
                "id" => $order?->branch?->id ?? null,
                "name" => $order?->branch
                ?->translations()
                ?->where("key", $order?->branch?->name)
                ?->where("locale", $locale)
                ?->first()
                ?->value ?? $order?->branch?->name ?? null,
                "address" => $order?->branch?->address ?? null,
                "email" => $order?->branch?->email ?? null,
                "phone" => $order?->branch?->phone ?? null,
                "count_orders" => $order?->branch?->count_orders ?? null,
            ],
            "user" => [
                "id" => $order?->user?->id ?? null,
                "f_name" => $order?->user?->f_name ?? null,
                "l_name" => $order?->user?->l_name ?? null,
                "email" => $order?->user?->email ?? null,
                "phone" => $order?->user?->phone ?? null,
                "phone_2" => $order?->user?->phone_2 ?? null,
                "name" => $order?->user?->name ?? null,
                "count_orders" => $order?->user?->count_orders ?? null,
            ],
            "admin" => [
                "id" => $order?->admin?->id ?? null,
                "name" => $order?->admin?->name ?? null,
            ],
            "delivery" => [
                "id" => $order?->delivery?->id ?? null,
                "f_name" => $order?->delivery?->f_name ?? null,
                "l_name" => $order?->delivery?->l_name ?? null,
                "phone" => $order?->delivery?->phone ?? null,
                "count_orders" => $order?->delivery?->count_orders ?? null,
            ],
            "address" => [
                "id" => $order?->address?->id ?? null,
                "address" => $order?->address?->address ?? null,
                "street" => $order?->address?->street ?? null,
                "building_num" => $order?->address?->building_num ?? null,
                "floor_num" => $order?->address?->floor_num ?? null,
                "apartment" => $order?->address?->apartment ?? null,
                "additional_data" => $order?->address?->additional_data ?? null,
                "type" => $order?->address?->type ?? null,
                "map" => $order?->address?->map ?? null,
                "zone" => [
                    "zone" => $order?->address?->zone?->zone ?? null,
                    "price" => $order?->address?->zone?->price ?? null,
                ],
                "city" => $order?->address?->zone?->city?->name ?? null,
            ], 
            "payment_method" => [ 
                "id" => $order?->payment_method?->id ?? null,
                "name" => $order?->payment_method
                ?->translations()
                ?->where("key", $order?->payment_method?->name)
                ?->where("locale", $locale)
                ?->first()
                ?->value ?? $order?->payment_method?->name ?? null,
                "logo" => $order?->payment_method?->logo_link ?? null
            ],
            "schedule" => $order?->schedule?->name ?? null
        ];
        
        return $order_arr;
    }
    
    public function order_preparation_format($order, $locale){
     
        $products = [];
        if(empty($order->order_details_data)){
            return null;
        }
        foreach ($order->order_details_data as $item) {
            # TranslationTbl
            $extras = [];
            $addons = [];
            $excludes = [];
            $variations = [];
            $product = [];
            foreach ($item['extras'] as $element) {
                $name = TranslationTbl::
                where("key", $element['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['name'];
                $extras[] = [
                    "id" => $element['id'],
                    "name" => $name,
                    "price" => $element['price'],
                ];
            }
            foreach ($item['addons'] as $element) {
                $name = TranslationTbl::
                where("key", $element['addon']['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['addon']['name'];
                $addons[] = [
                    "id" => $element['addon']['id'],
                    "name" => $name,
                    "price" => $element['addon']['price'],
                ];
            }
            foreach ($item['excludes'] as $element) {
                $name = TranslationTbl::
                where("key", $element['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['name'];
                $excludes[] = [
                    "id" => $element['id'],
                    "name" => $name,
                ];
            }
            foreach ($item['variations'] as $element) {
                $name = TranslationTbl::
                where("key", $element['variation']['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['variation']['name'];
                $options = [];
                foreach ($element['options'] as $value) {
                    $option_name = TranslationTbl::
                    where("key", $value['name'])
                    ->where("locale", $locale)
                    ->orderByDesc("id")
                    ->first()?->value ?? $value['name'];
                    $options[] = [
                        "id" => $value['id'],
                        "name" => $option_name,
                        //"price" => $value['price'],
                        //"total_option_price" => $value['total_option_price'],
                    ];
                }
                $variations[] = [
                    "id" => $element['variation']['id'],
                    "name" => $name,
                    //"price" => $element['variation']['price'],
                    "options" => $options,
                ];
            }
            if(isset($item['product'][0]['product'])){
                $name = TranslationTbl::
                where("key", $item['product'][0]['product']['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $item['product'][0]['product']['name'];
                $product = [
                    'id' => $item['product'][0]['product']['id'],
                    'name' => $name,  
                    'count' => $item['product'][0]['count'], 
                ];
            }  
            $product_item = [
                "extras" => $extras,
                "addons" => $addons,
                "addons" => $addons,
                "variations" => $variations,
                "product" => $product,
            ];
            $products[] = $product_item;
        }
        $order_arr = [
            "id" => $order->id,
            "order_details" => $products,
            "order_type" => $order->order_type,
            "take_away_status" => $order->take_away_status,
            "delivery_status" => $order->delivery_status,
            "order_number" => $order->order_number, 
        ];
        
        return $order_arr;
    }
}