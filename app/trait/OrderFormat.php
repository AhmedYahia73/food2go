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
                where("key", $element['name'])
                ->where("locale", $locale)
                ->orderByDesc("id")
                ->first()?->value ?? $element['name'];
                $addons[] = [
                    "id" => $element['id'],
                    "name" => $name,
                    "price" => $element['price'],
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
                "id" => $order?->branch?->id,
                "name" => $order?->branch?->name,
                "address" => $order?->branch?->address,
                "email" => $order?->branch?->email,
                "phone" => $order?->branch?->phone,
            ],
            "user" => [
                "id" => $order?->user?->id,
                "f_name" => $order?->user?->f_name,
                "l_name" => $order?->user?->l_name,
                "email" => $order?->user?->email,
                "phone" => $order?->user?->phone,
                "phone_2" => $order?->user?->phone_2,
                "name" => $order?->user?->name,
            ],
            "admin" => [
                "id" => $order?->admin?->id,
                "name" => $order?->admin?->name,
            ],
            "delivery" => [
                "id" => $order?->delivery?->id,
                "f_name" => $order?->delivery?->f_name,
                "l_name" => $order?->delivery?->l_name,
                "phone" => $order?->delivery?->phone,
            ],
            "address" => [
                "id" => $order?->address?->id,
                "address" => $order?->address?->address,
                "street" => $order?->address?->street,
                "building_num" => $order?->address?->building_num,
                "floor_num" => $order?->address?->floor_num,
                "apartment" => $order?->address?->apartment,
                "additional_data" => $order?->address?->additional_data,
                "type" => $order?->address?->type,
                "map" => $order?->address?->map,
                "zone" => [
                    "zone" => $order?->address?->zone?->zone,
                    "price" => $order?->address?->zone?->price,
                ],
                "city" => $order?->address?->zone?->city?->name,
            ],  
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
                    "id" => $element['id'],
                    "name" => $name,
                    "price" => $element['price'],
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