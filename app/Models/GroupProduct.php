<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupProduct extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'name',
        'increase_precentage',
        'decrease_precentage',
        'due',
        'balance',
        'module',
        'status', 
    ];

    public function un_active_products(){
        return $this->belongsToMany(Product::class, "product_group_product", "group_product_id", "product_id");
    }

    public function products_price(){
        return $this->hasMany(GroupPrice::class, "group_product_id");
    }
}
