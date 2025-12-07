<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryProductHistory extends Model
{
    protected $fillable = [ 
        'category_id',
        'product_id',
        'inventory_id',
        'quantity',
        'actual_quantity',
        'inability',
        'cost',
    ];

    public function category(){
        return $this->belongsTo(PurchaseCategory::class, "category_id");
    }

    public function product(){
        return $this->belongsTo(PurchaseProduct::class, "product_id");
    }
}
