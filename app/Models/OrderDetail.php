<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'product_id',
        'exclude_id',
        'extra_id',
        'variation_id',
        'option_id',
        'order_id',
        'count',
        'deal_id',
        'product_index',
    ];
}
