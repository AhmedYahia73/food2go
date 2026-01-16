<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $locale = app()->getLocale(); // Use the application's current locale
        if ($this?->taxes?->setting && $this?->taxes?->setting == 'included') {
            $price =  empty($this->tax) ? $this->price: 
            ($this->tax->type == 'value' ? $this->price + $this->tax->amount : $this->price + $this->tax->amount * $this->price / 100);

            $tax = $price;
            $discount = $price;
            $addon_arr = [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $price,
                'price_after_tax' => $tax,
                'price_after_discount' => $discount,
                'final_price' =>  $discount * ($tax - $price) / 100 + $discount,
                'discount_val' => $price - $discount,
                'tax_val' => $tax - $price,
                'tax_id' => $this->tax_id,
                'quantity_add' => $this->quantity_add,
                'tax' => $this->whenLoaded('tax'),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];    
            if ($this->discount && !empty($this->discount) && $this->discount->type == 'precentage') {
                $discount = $price - $this->discount->amount * $price / 100;
                $addon_arr['price_after_discount'] = $discount;
                $addon_arr['final_price'] = $discount * ($tax - $price) / 100 + $discount; 
            }   
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
            $discount = $price;
            $addon_arr = [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $price,
                'price_after_tax' => $tax,
                'price_after_discount' => $discount,
                'final_price' =>  $discount * ($tax - $price) / 100 + $discount,
                'discount_val' => 0,
                'tax_val' => $tax - $price,
                'tax_id' => $this->tax_id,
                'quantity_add' => $this->quantity_add,
                'tax' => $this->whenLoaded('tax'),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
            if ($this->discount && !empty($this->discount) && $this->discount->type == 'precentage') {
                $discount = $price - $this->discount->amount * $price / 100;
                $addon_arr['price_after_discount'] = $discount;
                $addon_arr['final_price'] = $discount * ($tax - $price) / 100 + $discount;
                $addon_arr['discount_val'] = $price - $discount;
            }

        }
        return $addon_arr;
    }
} 
