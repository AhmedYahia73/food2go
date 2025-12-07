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
        'acual_quantity',
        'inability',
        'cost',
    ];

    public function category(){
        return $this->belongsTo(PurchaseCategory::class, "category_id");
    }

    public function material(){
        return $this->belongsTo(Material::class, "material_id");
    }
}
