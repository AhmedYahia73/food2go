<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class ProductPricing extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'product_id',
        'branch_id',
        'price',
    ];
}
