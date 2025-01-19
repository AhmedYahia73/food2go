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
        $locale = app()->getLocale(); // Use the application's current locale
        if ($this->taxes->setting == 'included') {
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => empty($this->product->tax) ? $this->price: 
                ($this->product->tax->type == 'value' ? $this->price + $this->product->tax->amount :$this->price + $this->product->tax->amount * $this->price / 100),
                'product_id' => $this->product_id,
                'variation_id' => $this->variation_id,
                'status' => $this->status,
                'points' => $this->points,
                'extra' => ExtraResource::collection($this->whenLoaded('extra')),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }
        else{
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $this->price,
                'product_id' => $this->product_id,
                'variation_id' => $this->variation_id,
                'status' => $this->status,
                'points' => $this->points,
                'extra' => ExtraResource::collection($this->whenLoaded('extra')),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }
    }
}
