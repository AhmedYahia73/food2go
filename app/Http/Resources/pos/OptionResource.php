<?php

namespace App\Http\Resources\pos;

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
        $total_discount = 0;
        $total_tax = 0;
        $locale = app()->getLocale(); // Use the application's current locale
        if ($this->taxes->setting == 'included') {
            $price = $this->price;
            if (!empty($this->product->discount) && $this->product->discount->type == 'precentage') {
                $discount = $price - $this->product->discount->amount * $price / 100;
                $total_discount = $this->product->discount->amount * $price / 100;
            }
            else{
                $discount = $price;
                $total_discount = $this->product->discount->amount;
            }
            $price = empty($this->product->tax) ? $discount: 
            ($this->product->tax->type == 'value' ? $discount 
            : $discount + $this->product->tax->amount * $discount / 100);
            $tax = $price;
            $total_tax = 0;
             
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $this->price,
                'total_discount' => $total_discount,
                'total_tax' => $total_tax ,
                'final_price' =>  $tax,
                'product_id' => $this?->product_id,
                'variation_id' => $this->variation_id,
                'status' => $this->status,
            ];
        }
        else{
            $price = $this->price;

            if (!empty($this->product->discount)) {
                if ($this->product->discount->type == 'precentage') {
                    $discount = $price - $this->product->discount->amount * $price / 100;
                    $total_discount = $this->product->discount->amount * $price / 100;
                } else {
                    $discount = $price - $this->product->discount->amount;
                    $total_discount = $this->product->discount->amount;
                }
            }
            else{
                $discount = $price;
            }
            
            if (!empty($this->product->tax)) {
                if ($this->product->tax->type == 'precentage') {
                    $tax = $discount + $this->product->tax->amount * $discount / 100;
                    $total_tax = $this->product->tax->amount * $discount / 100;
                } else {
                    $tax = $discount + $this->product->tax->amount;
                    $total_tax = $this->product->tax->amount;
                }
            }
            else{
                $tax = $discount;
            }
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $this->price,
                'total_discount' => $total_discount,
                'total_tax' => $total_tax ,
                'final_price' =>  $tax,
                'product_id' => $this?->product_id,
                'variation_id' => $this->variation_id,
                'status' => $this->status,
            ];
        }
    }
}
