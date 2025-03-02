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
        if ($this->taxes->setting == 'included') {
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => empty($this->tax) ? $this->price: 
                ($this->tax->type == 'value' ? $this->price + $this->tax->amount : $this->price + $this->tax->amount * $this->price / 100),
                'tax_id' => $this->tax_id,
                'quantity_add' => $this->quantity_add,
                'tax' => $this->whenLoaded('tax'),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }
        else {
            return [
                'id' => $this->id,
                'name' => $this->translations->where('key', $this->name)->first()?->value ?? $this->name,
                'price' => $this->price,
                'tax_id' => $this->tax_id,
                'quantity_add' => $this->quantity_add,
                'tax' => $this->whenLoaded('tax'),
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
            ];
        }
    }
} 
