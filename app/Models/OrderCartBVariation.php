<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCartBVariation extends Model
{
    protected $fillable = [
        "order_cart_id",
        "variation_id", 
        "order_cart_b_id",
    ];

    public function variation(){
        return $this->belongsTo(VariationProduct::class, 'variation_id');
    }

    public function options(){
        return $this->hasMany(OrderCartBOption::class, 'variation_bundle_id');
    }
}
