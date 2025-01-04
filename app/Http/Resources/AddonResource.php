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
