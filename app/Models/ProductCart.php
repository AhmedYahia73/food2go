<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCart extends Model
{ 
    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'note',
        'user_id'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variations_cart()
    {
        return $this->hasMany(VariationCart::class, "product_cart_id");
    }

    public function addons_cart()
    {
        return $this->hasMany(AddonCart::class, "product_cart_id");
    }
}
