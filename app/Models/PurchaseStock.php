<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_id',
        'store_id',
        'quantity',
        'actual_quantity',
        'unit_id',
    ];

    public function category(){
        return $this->belongsTo(PurchaseCategory::class, 'category_id');
    }

    public function product(){
        return $this->belongsTo(PurchaseProduct::class, 'product_id');
    }

    public function store(){
        return $this->belongsTo(PurchaseStore::class, 'store_id');
    }

    public function unit(){
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
