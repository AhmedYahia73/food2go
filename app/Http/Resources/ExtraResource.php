<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtraResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale(); // Use the application's current locale
        if ($this->taxes->setting == 'included') {
            $price = empty($this->product->tax) ? $this->price: 
            ($this->product->tax->type == 'value' ? $this->price + $this->product->tax->amount 
            : $this->price + $this->product->tax->amount * $this->price / 100);
            
            if (!empty($this->product->discount) && $this->product->discount->type == 'precentage') {
                $discount = $price - $this->product->discount->amount * $price / 100;
            }
            else{
                $discount = $price;
            }
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $price,
                'price_after_discount' => $discount,
                'product_id' => $this->product_id,
                'variation_id' => $this->variation_id,
                'extra_id' => $this->extra_id,
                'option_id' => $this->option_id,
                'parent_extra' => $this->parent_extra,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }
        else{
            $price = $this->price;
            
            if (!empty($this->product->discount) && $this->product->discount->type == 'precentage') {
                $discount = $price - $this->product->discount->amount * $price / 100;
            }
            else{
                $discount = $price;
            }
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $this->price,
                'product_id' => $this->product_id,
                'variation_id' => $this->variation_id,
                'extra_id' => $this->extra_id,
                'option_id' => $this->option_id,
                'parent_extra' => $this->parent_extra,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }
    }
}
