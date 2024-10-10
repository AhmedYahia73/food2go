<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'product_id',
        'variation_id',
    ];
}
