<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderVariationBundle extends Model
{
    protected $fillable = [
        "order_bundle_id",
        "variation_id",
    ];

    public function order_bundle(){
        return $this->belongsTo(OrderBundle::class, 'order_bundle_id');
    }

    public function variation(){
        return $this->belongsTo(VariationProduct::class, 'variation_id');
    }
}
