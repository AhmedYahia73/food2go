<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $allExtras = [];
        $allExtras = $this->extra->toArray();  

        if (!empty($this->addons) && !empty($this->category_addons) && !empty($this->sub_category_addons)) {   
            $addons = collect([])
            ->merge(AddonResource::collection($this->whenLoaded('addons')))
            ->merge(AddonResource::collection($this->whenLoaded('category_addons')))
            ->merge(AddonResource::collection($this->whenLoaded('sub_category_addons')));
        }
        elseif (!empty($this->addons) && !empty($this->category_addons)) {   
            $addons = collect([])
            ->merge(AddonResource::collection($this->whenLoaded('addons')))
            ->merge(AddonResource::collection($this->whenLoaded('category_addons')));
        }
        else{  
            $addons = AddonResource::collection($this->whenLoaded('addons'));
        }
    
        $today = date("Y-m-d");
        $my_discount = ((empty($this?->discount?->start_date) || $this?->discount?->start_date <= $today)
        && (empty($this?->discount?->end_date) || $this?->discount?->end_date >= $today)) ? $this?->discount
        : null;
        $locale = app()->getLocale(); // Use the application's current locale
        if ($this->taxes->setting == 'included') {
            $original_price = $this->price; // السعر الأصلي (شامل الضريبة)

            // 1. استخراج (السعر قبل الضريبة) و (قيمة الضريبة) من السعر الأصلي مباشرة
            $tax_amount = 0;
            $price_before_tax = $original_price;

            if (!empty($this->tax)) {
                if ($this->tax->type == 'value') {
                    $tax_amount = $this->tax->amount;
                    $price_before_tax = max(0, $original_price - $tax_amount);
                } else {
                    // معادلة استخراج السعر الأساسي إذا كانت الضريبة نسبة مئوية ومشمولة
                    $price_before_tax = $original_price / (1 + ($this->tax->amount / 100));
                    $tax_amount = $original_price - $price_before_tax;
                }
            }

            // السعر الصافي قبل الضريبة وقبل الخصم (تُخصم منه الضريبة فقط)
            $price = $price_before_tax;

            // 2. حساب قيمة الخصم والسعر بعد الخصم
            $discount_val = 0;
            if (!empty($my_discount)) {
                if ($my_discount->type == 'precentage') {
                    $discount_val = ($my_discount->amount * $original_price / 100);
                } else {
                    $discount_val = $my_discount->amount;
                }
            }
            $final_price = max(0, $original_price - $discount_val);
            $discount = $final_price;

            return [
                'id' => $this->id,
                'allExtras' => ExtraResource::collection($this->whenLoaded('extra')),
                'taxes' => $this->taxes->setting,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'description' => $this->translations->where('key', $this->description)->first()?->value ?? $this->description,
                'image' => $this->image,
                'category_id' => $this->category_id,
                'sub_category_id' => $this->sub_category_id,
                'item_type' => $this->item_type,
                'stock_type' => $this->stock_type,
                'number' => $this->number,
                'price' => $price, // السعر قبل الضريبة 
                'price_after_discount' => $discount, // السعر بعد الخصم (لكنه شامل الضريبة)
                'price_after_tax' => $final_price, // السعر النهائي المطلوب دفعه
                'final_price' =>  $final_price,
                'discount_val' => $original_price - $discount, // قيمة الخصم الفعلية
                'tax_only' => round($tax_amount, 2), // قيمة الضريبة فقط المستخرجة
                'tax_val' => round($tax_amount, 2),
                'product_time_status' => $this->product_time_status,
                'from' => $this->from,
                'to' => $this->to,
                'discount_id' => $this->discount_id,
                'tax_id' => $this->tax_id,
                'status' => $this->status,
                'recommended' => $this->recommended,
                'points' => $this->points,
                'image_link' => $this->image_link,
                'orders_count' => $this->orders_count,
                'category' => CategoryResource::collection($this->whenLoaded('category')),
                'subCategory' => CategoryResource::collection($this->whenLoaded('subCategory')),
                'discount' => $this->whenLoaded('discount'),
                'tax' => $this->whenLoaded('tax'),
                'group_products' => GroupProductResource::collection($this->whenLoaded('group_products')),
                'addons' => $addons, 
                'excludes' => ExcludeResource::collection($this->whenLoaded('excludes')), 
                'variations' => VariationResource::collection($this->whenLoaded('variations')),
                'favourite_product' => $this->whenLoaded('favourite_product'),
                'sales_count' => $this->whenLoaded('sales_count'),
                'favourite' => is_bool($this->favourites) ? $this->favourite : false,
                'tax_obj' => $this->tax,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'weight_status' => $this->weight_status ?? 0,
                'product_code' => $this->product_code,
                'product_time' => (bool)($this->product_time ?? false),
                'unit_time' => $this->unit_time,
                'extra_time' => (bool)($this->extra_time ?? false),
                'extra_unit_time' => $this->extra_unit_time,
                'extra_time_price' => $this->extra_time_price,
                'min_time' => $this->min_time ?? 0,
            ];
        }
        else {
            $price = $this->price;

            if (!empty($my_discount)) {
                if ($my_discount->type == 'precentage') {
                    $discount = $price - $my_discount->amount * $price / 100;
                } else {
                    $discount = $price - $my_discount->amount;
                }
            }
            else{
                $discount = $price;
            }
            
            if (!empty($this->tax)) {
                if ($this->tax->type == 'precentage') {
                    $tax = $discount + $this->tax->amount * $discount / 100;
                } else {
                    $tax = $discount + $this->tax->amount;
                }
            }
            else{
                $tax = $discount;
            }
            return [
                'id' => $this->id,
                'allExtras' => ExtraResource::collection($this->whenLoaded('extra')),
                'taxes' => $this->taxes->setting,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'description' => $this->translations->where('key', $this->description)->first()?->value ?? $this->description,
                'image' => $this->image,
                'category_id' => $this->category_id,
                'sub_category_id' => $this->sub_category_id,
                'item_type' => $this->item_type,
                'stock_type' => $this->stock_type,
                'group_products' => GroupProductResource::collection($this->whenLoaded('group_products')),
                'number' => $this->number,
                'price' => $price,
                'price_after_discount' => $discount,
                'price_after_tax' => $tax,
                'final_price' =>  $tax,
                'discount_val' => $price - $discount,
                'tax_only' => round($tax - $discount, 2),
                'tax_val' => round($tax - $price, 2),
                'product_time_status' => $this->product_time_status,
                'from' => $this->from,
                'to' => $this->to,
                'discount_id' => $this->discount_id,
                'tax_id' => $this->tax_id,
                'status' => $this->status,
                'recommended' => $this->recommended,
                'points' => $this->points,
                'image_link' => $this->image_link,
                'orders_count' => $this->orders_count,
                'category' => CategoryResource::collection($this->whenLoaded('category')),
                'subCategory' => CategoryResource::collection($this->whenLoaded('subCategory')),
                'discount' => $this->whenLoaded('discount'),
                'tax' => $this->whenLoaded('tax'),
                'addons' => $addons, 
                'excludes' => ExcludeResource::collection($this->whenLoaded('excludes')), 
                'variations' => VariationResource::collection($this->whenLoaded('variations')),
                'favourite_product' => $this->whenLoaded('favourite_product'),
                'sales_count' => $this->whenLoaded('sales_count'),
                'favourite' => is_bool($this->favourites) ? $this->favourite : false,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'tax_obj' => $this->tax,
                'weight_status' => $this->weight_status ?? 0,
                'product_code' => $this->product_code,
                'product_time' => (bool)($this->product_time ?? false),
                'unit_time' => $this->unit_time,
                'extra_time' => (bool)($this->extra_time ?? false),
                'extra_unit_time' => $this->extra_unit_time,
                'extra_time_price' => $this->extra_time_price,
                'min_time' => $this->min_time ?? 0,
            ];
        }
    }
}
