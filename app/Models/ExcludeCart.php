<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class ExcludeCart extends Model
{
    protected $fillable = [
        'exclude_id',
        'product_id',
        'product_cart_id',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function exclude()
    {
        return $this->belongsTo(ExcludeProduct::class, 'exclude_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
