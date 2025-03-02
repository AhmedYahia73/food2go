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
        $addons = collect([])
        ->merge(AddonResource::collection($this->whenLoaded('category_addons')))
        ->merge(AddonResource::collection($this->whenLoaded('sub_category_addons')))
        ->merge(AddonResource::collection($this->whenLoaded('addons')));
    
        $locale = app()->getLocale(); // Use the application's current locale
        if ($this->taxes->setting == 'included') {
            return [
                'id' => $this->id,
                'taxes' => $this->taxes->setting,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'description' => $this->translations->where('key', $this->description)->first()?->value ?? $this->description,
                'image' => $this->image,
                'category_id' => $this->category_id,
                'sub_category_id' => $this->sub_category_id,
                'item_type' => $this->item_type,
                'stock_type' => $this->stock_type,
                'number' => $this->number,
                'price' => empty($this->tax) ? $this->price: 
                ($this->tax->type == 'value' ? $this->price + $this->tax->amount :$this->price + $this->tax->amount * $this->price / 100),
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
                'extra' => ExtraResource::collection($this->whenLoaded('extra')),
                'variations' => VariationResource::collection($this->whenLoaded('variations')),
                'favourite_product' => $this->whenLoaded('favourite_product'),
                'sales_count' => $this->whenLoaded('sales_count'),
                'favourite' => $this->favourite ?? false,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        } 
        else {
            return [
                'id' => $this->id,
                'taxes' => $this->taxes->setting,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'description' => $this->translations->where('key', $this->description)->first()?->value ?? $this->description,
                'image' => $this->image,
                'category_id' => $this->category_id,
                'sub_category_id' => $this->sub_category_id,
                'item_type' => $this->item_type,
                'stock_type' => $this->stock_type,
                'number' => $this->number,
                'price' => $this->price,
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
                'extra' => ExtraResource::collection($this->whenLoaded('extra')),
                'variations' => VariationResource::collection($this->whenLoaded('variations')),
                'favourite_product' => $this->whenLoaded('favourite_product'),
                'sales_count' => $this->whenLoaded('sales_count'),
                'favourite' => $this->favourite ?? false,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }
    }
}
