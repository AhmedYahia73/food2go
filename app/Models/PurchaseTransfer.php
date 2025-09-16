<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_store_id',
        'to_store_id',
        'product_id',
        'quintity',
        'admin_id',
        'category_id',
        'status',
    ];
}
