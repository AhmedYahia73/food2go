<?php

namespace App\Http\Resources\pos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VariationResource extends JsonResource
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
            'type' => $this->type,
            'min' => $this->min,
            'max' => $this->max,
            'required' => $this->required,
            'product_id' => $this->product_id,
            'extra' => ExtraResource::collection($this->whenLoaded('extra')),
            'options' => OptionResource::collection($this->whenLoaded('options')),
            'weight' => $this->weight,
            'weight_unit' => $this->weight_unit,
        ];
    }
}
