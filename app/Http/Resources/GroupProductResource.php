<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GroupProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    { 
        return [
            'id' => $this->id,
            'name' => $this->translations->where('key', $this->name)
            ->where('locale', $locale)->first()?->value ?? $this->name,
            'status' => $this->status,
            'products' => ProductUpsalingResource::collection($this->whenLoaded('products'))
            ->additional([
                'locale' => app()->getLocale()
            ]),
        ];
    }
}
