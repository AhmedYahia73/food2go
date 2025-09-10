<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseConsumersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_id',
        'branch_id',
        'store_id',
        'admin_id',
        'quintity',
    ];
}
