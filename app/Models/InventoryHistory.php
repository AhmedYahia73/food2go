<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryHistory extends Model
{ 
    protected $fillable = [ 
        'admin_id',
    ];

    public function materials(){
        return $this->hasMany(InventoryMaterialHistory::class, "inventory_id");
    }

    public function products(){
        return $this->hasMany(InventoryProductHistory::class, "inventory_id");
    }
}
