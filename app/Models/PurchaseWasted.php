<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseWasted extends Model
{ 
    use HasFactory;

    protected $fillable = [ 
        'category_id',
        'product_id',
        'store_id', 
        'quantity',
        'status',
        'material_id',
        'category_material_id',
        'reason',
    ];

    public function material(){
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function category_material(){
        return $this->belongsTo(MaterialCategory::class, 'category_material_id');
    }

    public function category(){
        return $this->belongsTo(PurchaseCategory::class, 'category_id');
    }

    public function product(){
        return $this->belongsTo(PurchaseProduct::class, 'product_id');
    }

    public function store(){
        return $this->belongsTo(PurchaseStore::class, 'store_id');
    }
}
