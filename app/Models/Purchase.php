<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'category_material_id',
        'material_id',
        "type",
        'product_id',
        'admin_id', 
        'store_id',
        'total_coast',
        'quintity',
        'receipt',
        'date',
        'unit_id',
    ];
    protected $appends = ['receipt_link'];

    public function financial(){
        return $this->belongsToMany(FinantiolAcounting::class, 'purchase_financials', 'financial_id', 'purchase_id')
        ->withPivot('amount');
    }

    public function getReceiptLinkAttribute(){
        if(isset($this->attributes['receipt'])){
            return url('storage/' . $this->attributes['receipt']);
        }
        return null;
    }

    public function material(){
        return $this->belongsTo(Material::class, 'material_id');
    }

    public function material_category(){
        return $this->belongsTo(MaterialCategory::class, 'category_material_id');
    }

    public function category(){
        return $this->belongsTo(PurchaseCategory::class, 'category_id');
    }

    public function product(){
        return $this->belongsTo(PurchaseProduct::class, 'product_id');
    }

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function store(){
        return $this->belongsTo(PurchaseStore::class, 'store_id');
    }

    public function unit(){
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
