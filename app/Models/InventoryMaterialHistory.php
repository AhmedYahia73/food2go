<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMaterialHistory extends Model
{
    protected $fillable = [
        'material_id',
        'cost',
        'quantity_from',
        'quantity_to',
        'inability',
        'inventory_id',
    ];
}
