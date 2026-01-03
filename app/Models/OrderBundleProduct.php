<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderBundleProduct extends Model
{

    protected $fillable = [
        "order_bundle_id",
        "product_id",
    ];

    public function products(){
        return $this->belongsTo(Product::class, "product_id");
    }

    public function variations(){
        return $this->hasMany(OrderVariationBundle::class, "order_bundle_p_id");
    }
}
