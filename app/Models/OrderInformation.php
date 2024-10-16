<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderInformation extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'number',
        'road',
        'house',
        'floor',
        'address',
        'order_id',
    ];
}
