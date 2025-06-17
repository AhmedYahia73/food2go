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
    
        $locale = app()->getLocale(); // Use the application's current locale
        if ($this->taxes->setting == 'included') {
            $price = empty($this->tax) ? $this->price: 
            ($this->tax->type == 'value' ? $this->price + $this->tax->amount 
            : $this->price + $this->tax->amount * $this->price / 100);
            
            if (!empty($this->discount)) {
                if ($this->discount->type == 'precentage') {
                    $discount = $price - $this->discount->amount * $price / 100;
                } else {
                    $discount = $price - $this->discount->amount;
                }
            }
            else{
                $discount = $price;
            }
            $tax = $price;
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
                'price' => $price,
                'price_after_discount' => $discount,
                'price_after_tax' => $tax,
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
                'favourite' => is_array($this->whenLoaded('favourite_product')) ? true : false,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        } 
        else {
            $price = $this->price;
            
            if (!empty($this->tax)) {
                if ($this->tax->type == 'precentage') {
                    $tax = $price + $this->tax->amount * $price / 100;
                } else {
                    $tax = $price + $this->tax->amount;
                }
            }
            else{
                $tax = $price;
            }

            if (!empty($this->discount)) {
                if ($this->discount->type == 'precentage') {
                    $discount = $price - $this->discount->amount * $price / 100;
                } else {
                    $discount = $price - $this->discount->amount;
                }
            }
            else{
                $discount = $price;
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
                'number' => $this->number,
                'price' => $price,
                'price_after_discount' => $discount,
                'price_after_tax' => $tax,
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
                'favourite' => !empty($this->whenLoaded('favourite_product')) ? true : false,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }
    }
}
