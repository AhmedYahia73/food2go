<?php

namespace App\Http\Resources\pos;

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
        $my_discount = $this?->product?->discount?->start_date <= date("Y-m-d")
        && $this?->product?->discount?->end_date >= date("Y-m-d") ? $this?->product?->discount
        : null;

        $locale = app()->getLocale(); // Use the application's current locale
       if ($this->product?->taxes?->setting == 'included') {
            $price_with_tax = $this->price;
            
            if (!empty($my_discount) && $my_discount->type == 'precentage') {
                $discounted_price_with_tax = $price_with_tax - $my_discount->amount * $price_with_tax / 100;
            } else {
                $discounted_price_with_tax = $price_with_tax;
            }

            if (empty($this->product->tax)) {
                $price_before_tax = $discounted_price_with_tax;
                $tax_val = 0;
            } else {
                if ($this->product->tax->type == 'value') {
                    $tax_val = 0; // Assuming value tax applies only to the base product
                    $price_before_tax = $discounted_price_with_tax;
                } else {
                    $price_before_tax = $discounted_price_with_tax / (1 + ($this->product->tax->amount / 100));
                    $tax_val = $discounted_price_with_tax - $price_before_tax;
                }
            }
            
            $base_price_before_tax = $price_before_tax + ($price_with_tax - $discounted_price_with_tax);

            return [
                'id' => $this->id,
                'price_after_discount' => $price_before_tax,
                'price_after_tax' => $discounted_price_with_tax,
                'final_price' =>  $discounted_price_with_tax,
                'tax_val' => round($tax_val, 2),
                'name' => TranslationTbl::where('key', $this->name)
                ->where('locale', $locale)->first()?->value ?? $this->name,
                'product_id' => $this->product_id,
                'variation_id' => $this->variation_id,
                'option_id' => $this->option_id,
                'min' => $this->min,
                'max' => $this->max,
                'price' => $base_price_before_tax,
            ]; 
        }
        else{
            $price = $this->price;
            
            if (!empty($my_discount) && $my_discount->type == 'precentage') {
                $discount = $price - $my_discount->amount * $price / 100;
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
