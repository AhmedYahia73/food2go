<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_id',
        'store_id',
        'quantity',
    ];
}
