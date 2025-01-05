<?php

namespace App\Http\Resources;

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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
