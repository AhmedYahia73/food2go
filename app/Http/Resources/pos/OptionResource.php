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
    $price = $this->price;
    $total_discount = 0;
    $total_tax = 0;

    $product = $this->product;
    $discountModel = $product?->discount;
    $taxModel = $product?->tax;
    $taxSetting = $this->taxes?->setting; // included | excluded

    /** --------------------
     * APPLY DISCOUNT
     * -------------------- */
    if ($discountModel) {
        if ($discountModel->type === 'percentage') {
            $total_discount = ($discountModel->amount * $price) / 100;
        } else {
            $total_discount = $discountModel->amount;
        }
    }

    $price_after_discount = max($price - $total_discount, 0);

    /** --------------------
     * APPLY TAX
     * -------------------- */
    if ($taxModel) {
        if ($taxModel->type === 'percentage') {
            $total_tax = ($taxModel->amount * $price_after_discount) / 100;
        } else {
            $total_tax = $taxModel->amount;
        }
    }

    /** --------------------
     * FINAL PRICE
     * -------------------- */
    if ($taxSetting === 'included') {
        $final_price = $price_after_discount;
        $total_tax = 0;
    } else {
        $final_price = $price_after_discount + $total_tax;
    }

    return [
        'id' => $this->id,
        'name' => $this->translations
            ->where('key', $this->name)
            ->first()?->value ?? $this->name,

        'price' => $this->price,
        'total_discount' => round($total_discount, 2),
        'total_tax' => round($total_tax, 2),
        'final_price' => round($final_price, 2),

        'product_id' => $this->product_id,
        'variation_id' => $this->variation_id,
        'status' => $this->status,
    ];
}
}