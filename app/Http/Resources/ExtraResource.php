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
        $my_discount = $this?->product?->discount?->start_date <= date("Y-m-d")
        && $this?->product?->discount?->end_date >= date("Y-m-d") ? $this?->product?->discount
        : null;

        $locale = app()->getLocale(); // Use the application's current locale

        if ($this->product?->taxes?->setting == 'included') {
            
            // نحتفظ بالسعر الأصلي (وهو هنا شامل الضريبة)
            $original_price = $this->price;
            
            // 1. حساب السعر بعد الخصم (هذا السعر سيظل شامل الضريبة)
            if (!empty($my_discount) && $my_discount->type == 'precentage') {
                $discount = $original_price - ($my_discount->amount * $original_price / 100);
            } else {
                $discount = $original_price;
            }
            
            // 2. السعر النهائي هو نفسه السعر بعد الخصم (لأن الضريبة مشمولة)
            $final_price = $discount;
            
            // 3. استخراج السعر الأساسي (قبل الضريبة)
            $price_before_tax = $final_price;
            
            $tax_module = $this->product->tax ?? null;
            
            if (!empty($tax_module)) {
                if ($tax_module->type == 'value') {
                    $price_before_tax = $final_price - $tax_module->amount;
                } else {
                    // معادلة الاستخراج العكسية لو الضريبة نسبة مئوية
                    $price_before_tax = $final_price / (1 + ($tax_module->amount / 100));
                }
            }
            
            return [
                'id' => $this->id,
                'price_after_discount' => $discount,      // السعر بعد الخصم (شامل الضريبة)
                'price_after_tax' => $final_price,       // السعر النهائي
                'final_price' =>  $final_price,          // السعر النهائي
                'name' => TranslationTbl::where('key', $this->name)
                    ->where('locale', $locale)->first()?->value ?? $this->name,
                'product_id' => $this->product_id,
                'variation_id' => $this->variation_id,
                'option_id' => $this->option_id,
                'min' => $this->min,
                'max' => $this->max,
                // التعديل هنا: السعر الصافي بدون ضريبة
                'price' => round($price_before_tax, 2),  
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
