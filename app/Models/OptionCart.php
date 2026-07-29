<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OptionCart extends Model
{
    protected $fillable = [
        'option_id',
        'variation_id',
        'product_id',
        'variation_cart_id',
        "quantity",
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    } 

    public function option()
    {
        return $this->belongsTo(OptionProduct::class, "option_id");
    }

    public function variation()
    {
        return $this->belongsTo(VariationProduct::class, "variation_id");
    } 
}
