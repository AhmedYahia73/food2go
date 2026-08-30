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
            
            // 1. نحتفظ بالسعر الأصلي (شامل الضريبة)
            $original_price = $this->price;
            $discount = $original_price;

            // 2. حساب السعر بعد الخصم (هذا السعر سيظل شامل للضريبة)
            if ($this->discount && !empty($this->discount) && $this->discount->type == 'precentage') {
                $discount = $original_price - ($this->discount->amount * $original_price / 100);
            }
            
            // 3. السعر النهائي هو نفسه السعر بعد الخصم لأن الضريبة مشمولة فيه
            $final_price = $discount;

            // 4. استخراج السعر قبل الضريبة وقيمتها من السعر النهائي
            $price_before_tax = $final_price;
            $tax_amount = 0;

            if (!empty($this->tax)) {
                if ($this->tax->type == 'value') {
                    $tax_amount = $this->tax->amount;
                    $price_before_tax = $final_price - $tax_amount;
                } else {
                    // المعادلة العكسية لو الضريبة نسبة مئوية
                    $price_before_tax = $final_price / (1 + ($this->tax->amount / 100));
                    $tax_amount = $final_price - $price_before_tax;
                }
            }

            // 5. بناء المصفوفة النهائية
            $addon_arr = [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => round($price_before_tax, 2), // السعر الصافي بدون ضريبة
                'price_after_tax' => $final_price,
                'price_after_discount' => $discount,
                'final_price' =>  $final_price,
                'discount_val' => round($original_price - $discount, 2), // قيمة الخصم
                'tax_val' => round($tax_amount, 2), // قيمة الضريبة المستخرجة
                'tax_id' => $this->tax_id,
                'quantity_add' => $this->quantity_add,
                'tax' => $this->whenLoaded('tax'),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];    
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
