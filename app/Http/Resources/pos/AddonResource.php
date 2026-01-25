<?php

namespace App\Http\Resources\pos;

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
        $total_discount = 0;
        $total_tax = 0;
        $locale = app()->getLocale(); // Use the application's current locale
        if ($this?->taxes?->setting && $this?->taxes?->setting == 'included') {
            $price =  empty($this->tax) ? $this->price: 
            ($this->tax->type == 'value' ? $this->price + $this->tax->amount : $this->price + $this->tax->amount * $this->price / 100);

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
            $addon_arr = [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $this->price,
                'final_price' => $discount,
                'total_discount' => $total_discount,
                'total_tax' => $total_tax, 
                'quantity_add' => $this->quantity_add,  
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
            $addon_arr = [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $this->price,
                'final_price' => $tax,
                'total_discount' => $total_discount,
                'total_tax' => $total_tax, 
                'quantity_add' => $this->quantity_add, 
            ]; 

        }
        return $addon_arr;
    }
}
