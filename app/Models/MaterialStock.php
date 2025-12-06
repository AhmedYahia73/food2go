<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialStock extends Model
{
    
    use HasFactory;

    protected $fillable = [
        'category_id',
        'material_id',
        'store_id',
        'quantity',
        'unit_id',
        'actual_quantity',
    ];
    protected $appends = ["inability"];

    public function getInabilityAttribute(){
        if(isset($this->attributes['quantity']) && isset($this->attributes['actual_quantity'])){
            return $this->attributes['quantity'] - $this->attributes['actual_quantity'];
        }
        else{
            return 0;
        }
    }

    public function category(){
        return $this->belongsTo(MaterialCategory::class, 'category_id');
    }

    public function material(){
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function store(){
        return $this->belongsTo(PurchaseStore::class, 'store_id');
    }

    public function unit(){
        return $this->belongsTo(Unit::class, 'unit_id');
    }

}
