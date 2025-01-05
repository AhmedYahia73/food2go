<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
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
            'title' => $this->translations->where('key', $this->title)->first()?->value ?? $this->title,
            'description' => $this->translations->where('key', $this->description)->first()?->value ?? $this->description,
            'image' => $this->image,
            'image_link' => $this->image_link,
            'price' => $this->price,
            'status' => $this->status,
            'daily' => $this->daily,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'times' => $this->whenLoaded('times'),
            'deal_customer' => $this->whenLoaded('deal_customer'),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
