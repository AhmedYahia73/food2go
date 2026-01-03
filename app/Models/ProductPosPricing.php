<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPosPricing extends Model
{ 
    protected $fillable = [
        'product_id',
        'module',
        'price',
    ];

    public function product(){
        return $this->belongsTo(Product::class, "product_id");
    }
}
