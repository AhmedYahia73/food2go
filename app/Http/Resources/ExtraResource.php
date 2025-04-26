<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExtraResource extends JsonResource
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
            'product_id' => $this->product_id,
            'pricing' => $this->pricing,
        ]; 
    }
}
