<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class VariationCart extends Model
{
    protected $fillable = [
        'variation_id',
        'product_id',
        'product_cart_id', 
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(VariationProduct::class, "variation_id");
    }

    public function options_cart()
    {
        return $this->hasMany(OptionCart::class, "variation_cart_id");
    }
}
