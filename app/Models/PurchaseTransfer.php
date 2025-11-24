<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseTransfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_store_id',
        'to_store_id',
        'product_id',
        'quintity',
        'admin_id',
        'category_id',
        'status',
        'unit_id',
        'material_id',
        'category_material_id',
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

    public function from_store(){
        return $this->belongsTo(PurchaseStore::class, 'from_store_id');
    }

    public function to_store(){
        return $this->belongsTo(PurchaseStore::class, 'to_store_id');
    }

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
