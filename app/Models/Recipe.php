<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_id',
        'weight',
        'store_category_id',
        'store_product_id',
        'status',
    ];

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function store_category(){
        return $this->belongsTo(PurchaseCategory::class, 'store_category_id');
    }

    public function store_product(){
        return $this->belongsTo(PurchaseProduct::class, 'store_product_id');
    }

    public function unit(){
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
