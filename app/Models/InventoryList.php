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
    ];

    public function store(){
        return $this->belongsTo(PurchaseStore::class, "store_id");
    }
}
