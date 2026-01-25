<?php

namespace App\Http\Resources\pos;

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
        $total_discount = 0;
        $total_tax = 0;
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
            $price = $this->price;
            if (!empty($this->discount)) {
                if ($this->discount->type == 'precentage') {
                    $discount = $price - $this->discount->amount * $price / 100;
                    $total_discount = $this->discount->amount * $price / 100;
                } else {
                    $discount = $price - $this->discount->amount;
                    $total_discount = $this->discount->amount;
                }
                $price = empty($this->tax) ? $discount: 
                ($this->tax->type == 'value' ? $discount + $this->tax->amount 
                : $discount + $this->tax->amount * $discount / 100);
            }
            else{
                $discount = $price;
                $price = empty($this->tax) ? $discount: 
                ($this->tax->type == 'value' ? $discount + $this->tax->amount 
                : $discount + $this->tax->amount * $discount / 100);
            }
            $total_tax = 0;
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
                'price' => $this->price,
                'total_discount' => $total_discount,
                'total_tax' => $total_tax,
                'final_price' =>  $tax,
                'image_link' => $this->image_link,
                'from' => $this->from,
                'to' => $this->to,
                'status' => $this->status,
                'addons' => $addons, 
                'excludes' => ExcludeResource::collection($this->whenLoaded('excludes')), 
                'variations' => VariationResource::collection($this->whenLoaded('variations')),
                'weight_status' => $this->weight_status ?? 0,
                'product_code' => $this->product_code,
            ];
        } 
        else {
            $price = $this->price;

            if (!empty($this->discount)) {
                if ($this->discount->type == 'precentage') {
                    $discount = $price - $this->discount->amount * $price / 100;
                    $total_discount = $this->discount->amount * $price / 100;
                } else {
                    $discount = $price - $this->discount->amount;
                    $total_discount = $this->discount->amount;
                }
            }
            else{
                $discount = $price;
            }
            
            if (!empty($this->tax)) {
                if ($this->tax->type == 'precentage') {
                    $tax = $discount + $this->tax->amount * $discount / 100;
                    $total_tax = $this->tax->amount * $discount / 100;
                } else {
                    $tax = $discount + $this->tax->amount;
                    $total_tax = $this->tax->amount;
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
                'price' => $this->price,
                'total_discount' => $total_discount,
                'total_tax' => $total_tax,
                'final_price' =>  $tax,
                'from' => $this->from,
                'to' => $this->to,
                'status' => $this->status,
                'image_link' => $this->image_link,
                'addons' => $addons, 
                'excludes' => ExcludeResource::collection($this->whenLoaded('excludes')), 
                'variations' => VariationResource::collection($this->whenLoaded('variations')),
                'weight_status' => $this->weight_status ?? 0,
                'product_code' => $this->product_code,
            ];
        }
    }
}
