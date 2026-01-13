<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

use App\Models\TranslationTbl;

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
       if ($this->product?->taxes?->setting == 'included') {
            $price = empty($this->product->tax) ? $this->price: 
            ($this->product->tax->type == 'value' ? $this->price 
            : $this->price + $this->product->tax->amount * $this->price / 100);
            
            if (!empty($this->product->discount) && $this->product->discount->type == 'precentage') {
                $discount = $price - $this->product->discount->amount * $price / 100;
            }
            else{
                $discount = $price;
            }
            $tax = $price;
            return [
                'id' => $this->id,
                'price_after_discount' => $discount,
                'price_after_tax' => $tax,
                'final_price' =>  $discount + $tax - $price,
                'name' => TranslationTbl::where('key', $this->name)
                ->where('locale', $locale)->first()?->value ?? $this->name,
                'product_id' => $this->product_id,
                'variation_id' => $this->variation_id,
                'option_id' => $this->option_id,
                'min' => $this->min,
                'max' => $this->max,
                'price' => $this->price,
            ]; 
        }
        else{
            $price = $this->price;
            
            if (!empty($this->product->tax)) {
                if ($this->product->tax->type == 'precentage') {
                    $tax = $price + $this->product->tax->amount * $price / 100;
                } else {
                    $tax = $price;
                }
            }
            else{
                $tax = $price;
            }
            if (!empty($this->product->discount) && $this->product->discount->type == 'precentage') {
                $discount = $price - $this->product->discount->amount * $price / 100;
            }
            else{
                $discount = $price;
            }
            return [
                'id' => $this->id,
                'price_after_discount' => $discount,
                'price_after_tax' => $tax,
                'final_price' =>  $discount + $tax - $price,
                'name' => TranslationTbl::where('key', $this->name)
                ->where('locale', $locale)->first()?->value ?? $this->name,
                'product_id' => $this->product_id,
                'variation_id' => $this->variation_id,
                'option_id' => $this->option_id,
                'min' => $this->min,
                'max' => $this->max,
                'price' => $this->price,
            ]; 
        }
    }
}
