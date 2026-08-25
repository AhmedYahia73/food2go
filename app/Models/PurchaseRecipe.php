<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PurchaseRecipe extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'weight', 
        'status',  
        'product_id', 
        'unit_id',
        'material_category_id',
        'material_product_id', 
    ];


    public function getIdAttribute($value){
        return (int) $value;
    }
    public function material_category(){
        return $this->belongsTo(MaterialCategory::class, "material_category_id");
    }

    public function material(){
        return $this->belongsTo(Material::class, "material_product_id");
    }

    public function unit(){
        return $this->belongsTo(Unit::class, "unit_id");
    }

    public function product(){
        return $this->belongsTo(PurchaseProduct::class, 'product_id');
    } 
}
