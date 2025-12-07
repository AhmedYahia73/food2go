<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryList extends Model
{
    protected $fillable = [ 
        'store_id',
        'product_num',
        'total_quantity',
        'cost',
        'status',
    ];

    public function store(){
        return $this->belongsTo(PurchaseStore::class, "store_id");
    }

    public function products(){
        return $this->hasMany(InventoryProductHistory::class, "inventory_id");
    }

    public function materials(){
        return $this->hasMany(InventoryMaterialHistory::class, "inventory_id");
    }
}
