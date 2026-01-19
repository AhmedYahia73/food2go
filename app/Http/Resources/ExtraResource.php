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
            
            $price = $this->price;
            if (!empty($this->product->discount) && $this->product->discount->type == 'precentage') {
                $discount = $price - $this->product->discount->amount * $price / 100;
            }
            else{
                $discount = $price;
            }
            $price = empty($this->product->tax) ? $discount: 
            ($this->product->tax->type == 'value' ? $discount 
            : $discount + $this->product->tax->amount * $discount / 100);
            $tax = $price;
            return [
                'id' => $this->id,
                'price_after_discount' => $discount,
                'price_after_tax' => $tax,
                'final_price' =>  $tax,
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
            
            if (!empty($this->product->discount) && $this->product->discount->type == 'precentage') {
                $discount = $price - $this->product->discount->amount * $price / 100;
            }
            else{
                $discount = $price;
            }
            if (!empty($this->product->tax)) {
                if ($this->product->tax->type == 'precentage') {
                    $tax = $discount + $this->product->tax->amount * $discount / 100;
                } else {
                    $tax = $discount;
                }
            }
            else{
                $tax = $discount;
            }
            return [
                'id' => $this->id,
                'price_after_discount' => $discount,
                'price_after_tax' => $tax,
                'final_price' =>  $tax,
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
