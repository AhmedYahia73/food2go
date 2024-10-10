<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'product_id',
        'variation_id'
    ];
}
