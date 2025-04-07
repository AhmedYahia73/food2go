<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OptionResource extends JsonResource
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
            $total_option_price = $price + $this->product->price;
            
            if (!empty($this->product->discount)) {
                if ($this->product->discount->type == 'precentage') {
                    $discount = $total_option_price - $this->product->discount->amount * $total_option_price / 100;
                } else {
                    $discount = $total_option_price - $this->product->discount->amount;
                }
            }
            else{
                $discount = $total_option_price;
            }
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $price,
                'total_option_price' => $total_option_price,
                'after_disount' => $discount,
                'product_id' => $this->product_id,
                'variation_id' => $this->variation_id,
                'status' => $this->status,
                'points' => $this->points,
                'extra' => ExtraResource::collection($this->whenLoaded('extra')),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                
            ];
        }
        else{
            $price = $this->price;
            $total_option_price = $price + $this->product->price;
            
            if (!empty($this->product->discount)) {
                if ($this->product->discount->type == 'precentage') {
                    $discount = $total_option_price - $this->product->discount->amount * $total_option_price / 100;
                } else {
                    $discount = $total_option_price - $this->product->discount->amount;
                }
            }
            else{
                $discount = $total_option_price;
            }
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $price,
                'total_option_price' => $total_option_price,
                'after_disount' => $discount, 
                'product_id' => $this->product_id,
                'variation_id' => $this->variation_id,
                'status' => $this->status,
                'points' => $this->points,
                'extra' => ExtraResource::collection($this->whenLoaded('extra')),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }
    }
}
