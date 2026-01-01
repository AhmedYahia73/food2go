<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCartBVariation extends Model
{
    protected $fillable = [
        "order_cart_id",
        "variation_id",
        "option_id",
        "order_cart_b_id",
    ];

    public function option(){
        return $this->belongsTo(OptionProduct::class, 'option_id');
    }
}
