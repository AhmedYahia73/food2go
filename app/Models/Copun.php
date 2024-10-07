<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Copun extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_id',
        'code',
        'start_date',
        'expire_date',
        'min_purchase',
        'max_discount',
        'discount',
        'discount_type',
        'order',
        'status',
        'limit',
    ];
}
