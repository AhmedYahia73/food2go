<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class PurchaseProduct extends Model
{ 
    use HasFactory, LogChanges;

    protected $fillable = [
        'name',
        'description',
        'status',
        'category_id',
        'min_stock',
    ];


    public function getIdAttribute($value){
        return (int) $value;
    }
    public function start_stock(){
        return $this->hasMany(ProductStore::class, 'product_id');
    }

    public function category(){
        return $this->belongsTo(PurchaseCategory::class, 'category_id');
    }

    public function stock(){
        return $this->hasOne(PurchaseStock::class, 'product_id');
    }

    public function stock_items(){
        return $this->hasMany(PurchaseStock::class, 'product_id');
    }
}
