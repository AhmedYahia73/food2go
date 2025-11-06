<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KitchenItem extends Model
{
    protected $fillable = [
        'kitchen_order_id',
        'product_id',
    ];

    public function product(){
        return $this->belongsTo(Product::class, "product_id");
    }

    public function excludes(){
        return $this->hasMany(KItemExclude::class, "kitchen_item_id");
    }
 
    public function extras(){
        return $this->hasMany(KItemExtra::class, "kitchen_item_id");
    }
 
    public function variation_selected(){
        return $this->hasMany(KItemVriation::class, "kitchen_item_id");
    }
 
    public function addons_selected(){
        return $this->hasMany(KItemAddon::class, "kitchen_item_id");
    }
}
