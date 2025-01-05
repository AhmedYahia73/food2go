<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BannerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {// Use the application's current locale
        return [
            'id' => $this->id,
            'image' => $this->translations
            ->where('key', $this->image)
            ->first()?->value ?? $this->image,
            'order' => $this->order,
            'category_id' => $this->category_id,
            'product_id' => $this->product_id,
            'deal_id' => $this->deal_id,
            'translation_id' => $this->translation_id,
            'status' => $this->status,
            'image_link' => url('storage/' . $this->translations
            ->where('key', $this->image)
            ->first()?->value ?? $this->image),
            'product' => ProductResource::collection($this->whenLoaded('product')),
            'category_banner' => CategoryResource::collection($this->whenLoaded('category_banner')),
            'deal' => $this->whenLoaded('deal'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
