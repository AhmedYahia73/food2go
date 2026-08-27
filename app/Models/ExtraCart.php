<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class ExtraCart extends Model
{
    protected $fillable = [
        'extra_id',
        'product_id',
        'product_cart_id',
        'quantity'
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function extra()
    {
        return $this->belongsTo(ExtraProduct::class, 'extra_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
