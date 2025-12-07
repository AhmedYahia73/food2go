<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMaterialHistory extends Model
{
    protected $fillable = [ 
        'category_id',
        'material_id',
        'inventory_id',
        'quantity', 
        'inability',
        'cost',
    ];

    public function category(){
        return $this->belongsTo(MaterialCategory::class, "category_id");
    }

    public function material(){
        return $this->belongsTo(Material::class, "material_id");
    }
}
