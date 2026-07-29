<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AddonCart extends Model
{
    use HasFactory;

    protected $fillable = [
        'addon_id',
        'product_id',
        'product_cart_id', 
        "quantity"
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, "addon_id");
    } 
}
