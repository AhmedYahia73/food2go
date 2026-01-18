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
            
            if (!empty($this?->product?->discount)) {
                if ($this?->product?->discount->type == 'precentage') {
                    $discount = $total_option_price - $this?->product?->discount->amount * $total_option_price / 100;
                } else {
                    $discount = $total_option_price;
                }
            }
            else{
                $discount = $total_option_price;
            }
            $price = empty($this?->product?->tax) ? $discount: 
            ($this?->product?->tax->type == 'value' ? $discount 
            : $discount + $this?->product?->tax->amount * $discount / 100);
            $total_option_price = $price + $this?->product?->price;
            $tax = $price;
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $price,
                'total_option_price' => $total_option_price,
                'after_disount' => $discount,
                'price_after_tax' => $tax,
                'final_price' =>  $tax,
                'discount_val' => $price - $discount,
                'tax_val' => $tax - $price,
                'product_id' => $this?->product_id,
                'variation_id' => $this->variation_id,
                'status' => $this->status,
                'points' => $this->points, 
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                
            ];
        }
        else{
            $price = $this->price;
            $total_option_price = $price + $this?->product?->price;
            
            
            if (!empty($this?->product?->discount)) {
                if ($this?->product?->discount->type == 'precentage') {
                    $discount = $total_option_price - $this?->product?->discount->amount * $total_option_price / 100;
                } else {
                    $discount = $total_option_price;
                }
            }
            else{
                $discount = $total_option_price;
            }
            if (!empty($this?->product?->tax)) {
                if ($this?->product?->tax->type == 'precentage') {
                    $tax = $discount + $this?->product?->tax->amount * $discount / 100;
                } else {
                    $tax = $discount;
                }
            }
            else{
                $tax = $discount;
            }
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $price,
                'total_option_price' => $total_option_price,
                'after_disount' => $discount, 
                'price_after_tax' => $tax,
                'final_price' =>  $price,
                'discount_val' => $price - $discount,
                'tax_val' => $tax - $price,
                'product_id' => $this?->product_id,
                'variation_id' => $this->variation_id,
                'status' => $this->status,
                'points' => $this->points, 
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }
    }
}
