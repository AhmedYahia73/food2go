<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryProductHistory extends Model
{
    protected $fillable = [
        'product_id',
        'quantity_from',
        'quantity_to',
        'inability',
        'cost',
        'inventory_id',
    ];
}
