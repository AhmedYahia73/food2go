<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    { // Use the application's current locale
        return [
            'id' => $this->id,
            'name' => $this->translations
            ->where('key', $this->name)
            ->first()?->value ?? $this->name,
            'image' => $this->image,
            'banner_image' => $this->banner_image,
            'category_id' => $this->category_id,
            'status' => $this->status,
            'priority' => $this->priority,
            'active' => $this->active,
            'image_link' => $this->image_link,
            'banner_link' => $this->banner_link,
            'sub_categories' => CategoryResource::collection($this->whenLoaded('sub_categories')),
            'parent_categories' => CategoryResource::collection($this->whenLoaded('parent_categories')),
            'parent' => CategoryResource::collection($this->whenLoaded('parent')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'category_products' => ProductResource::collection($this->whenLoaded('category_products')),
            'addons' => AddonResource::collection($this->whenLoaded('addons')),
           //'tax' => ExtraResource::collection($this->whenLoaded('extras')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
