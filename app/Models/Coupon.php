<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Product;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'title',
        'code',
        'start_date',
        'expire_date',
        'min_purchase',
        'max_discount_status',
        'max_discount',
        'product',
        'number_usage_status',
        'number_usage',
        'number_usage_user_status',
        'number_usage_user',
        'discount_type',
        'discount',
        'status',
    ];

    public function products(){
        return $this->belongsToMany(Product::class, 'product_coupon');
    }
}
