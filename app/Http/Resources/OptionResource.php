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
        
        $my_discount = $this?->product?->discount?->start_date <= date("Y-m-d")
            && $this?->product?->discount?->end_date >= date("Y-m-d") ? $this?->product?->discount
            : null;
        $locale = app()->getLocale(); // Use the application's current locale
        $tax_module = $this->new_tax ?? $this?->product?->tax;

        if ($this->taxes->setting == 'included') {
            
            // نحتفظ بالسعر الأصلي للأوبشن (والذي يشمل الضريبة)
            $original_price = $this->price; 
            
            // 1. حساب السعر بعد الخصم (هذا السعر سيظل شامل للضريبة)
            if (!empty($my_discount)) {
                if ($my_discount->type == 'precentage') {
                    $discount = $original_price - ($my_discount->amount * $original_price / 100);
                } else {
                    // حافظت عليها كما هي في كودك (يبدو أن الخصم الثابت لا يطبق على الأوبشن)
                    $discount = $original_price; 
                }
            } else {
                $discount = $original_price;
            }

            // 2. بما أن الضريبة مشمولة، السعر النهائي هو نفسه السعر بعد الخصم
            $final_price = $discount;

            // 3. استخراج (السعر قبل الضريبة) و (قيمة الضريبة)
            $tax_amount = 0;
            $price_before_tax = $final_price;

            if (!empty($tax_module)) {
                if ($tax_module->type == 'value') {
                    $tax_amount = $tax_module->amount;
                    $price_before_tax = $final_price - $tax_amount;
                } else {
                    // معادلة الاستخراج العكسية للنسبة المئوية
                    $price_before_tax = $final_price / (1 + ($tax_module->amount / 100));
                    $tax_amount = $final_price - $price_before_tax;
                }
            }

            // حساب الإجمالي بجمع السعر الشامل للأوبشن مع السعر الشامل للمنتج
            $total_option_price = $original_price + $this?->product?->price; 
            
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                // التعديلات تمت هنا لتعكس الأرقام الصحيحة
                'price' => round($final_price, 2), 
                'total_option_price' => $total_option_price,
                'after_disount' => $discount,
                'price_after_tax' => $final_price,
                'final_price' =>  $final_price,
                'discount_val' => round($original_price - $discount, 2),
                'tax_val' => round($tax_amount, 2),
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
            
            
            if (!empty($my_discount)) {
                if ($my_discount->type == 'precentage') {
                    $discount = $price - $my_discount->amount * $price / 100;
                } else {
                    $discount = $price;
                }
            }
            else{
                $discount = $price;
            }
            if (!empty($tax_module)) {
                if ($tax_module->type == 'precentage') {
                    $tax = $discount + $tax_module->amount * $discount / 100;
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
    }
}
