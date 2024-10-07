<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'image',
        'category_id',
        'sub_category_id',
        'item_type',
        'stock_type',
        'number',
        'price',
        'product_time_status',
        'from',
        'to',
        'discount_id',
        'tax_id',
        'tags',
        'status',
        'recommended',
    ];
}
