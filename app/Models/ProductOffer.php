<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOffer extends Model
{
    protected $fillable = [
        'name',
        'module',
        'start_date',
        'end_date',
        'discount',
        'time_from',
        'time_to',
        'delay',
        'days',
    ];

    public function products(){
        return $this->belongsToMany(Product::class, "product_offer_product", "product_offer_id", "product_id");
    }

    protected function casts(): array
    {
        return [
            'module' => 'array',
            'days' => 'array',
        ];
    }

    protected $hidden = [
        'pivot',
    ];
}
