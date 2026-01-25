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
    $total_discount = 0;
    $total_tax = 0;
    $locale = app()->getLocale();

    // ⛑ NULL-SAFE ACCESS
    $taxSetting = $this->taxes?->setting;
    $discountModel = $this->product?->discount;
    $taxModel = $this->product?->tax;

    if ($taxSetting === 'included') {

        $price = $this->price;

        if ($discountModel && $discountModel->type === 'precentage') {
            $total_discount = $discountModel->amount * $price / 100;
            $discount = $price - $total_discount;
        } elseif ($discountModel) {
            $total_discount = $discountModel->amount;
            $discount = $price;
        } else {
            $discount = $price;
        }

        $price = ($taxModel && $taxModel->type === 'precentage')
            ? $discount + ($taxModel->amount * $discount / 100)
            : $discount;

        $tax = $price;
        $total_tax = 0;

        return [
            'id' => $this->id,
            'name' => $this->translations
                ->where('key', $this->name)
                ->first()?->value ?? $this->name,
            'price' => $this->price,
            'total_discount' => $total_discount,
            'total_tax' => $total_tax,
            'final_price' => $tax,
            'product_id' => $this->product_id,
            'variation_id' => $this->variation_id,
            'status' => $this->status,
        ];
    }

    /* ---------------- TAX NOT INCLUDED ---------------- */

    $price = $this->price;

    if ($discountModel) {
        if ($discountModel->type === 'precentage') {
            $total_discount = $discountModel->amount * $price / 100;
            $discount = $price - $total_discount;
        } else {
            $total_discount = $discountModel->amount;
            $discount = $price - $total_discount;
        }
    } else {
        $discount = $price;
    }

    if ($taxModel) {
        if ($taxModel->type === 'precentage') {
            $total_tax = $taxModel->amount * $discount / 100;
            $tax = $discount + $total_tax;
        } else {
            $total_tax = $taxModel->amount;
            $tax = $discount + $total_tax;
        }
    } else {
        $tax = $discount;
    }

    return [
        'id' => $this->id,
        'name' => $this->translations
            ->where('key', $this->name)
            ->first()?->value ?? $this->name,
        'price' => $this->price,
        'total_discount' => $total_discount,
        'total_tax' => $total_tax,
        'final_price' => $tax,
        'product_id' => $this->product_id,
        'variation_id' => $this->variation_id,
        'status' => $this->status,
    ];
}
}
