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

   public function getModuleAttribute()
    {
        $raw = $this->attributes['module'] ?? null;

        // لو فاضي → رجع null
        if (!$raw) {
            return null;
        }

        // فكّ JSON
        $decoded = json_decode($raw, true);

        // لو مش Array → رجع null
        if (!is_array($decoded)) {
            return null;
        }

        // رجّع الـ Array
        return $decoded;
    }


    public function getModuleAttribute()
    {
        $decoded = json_decode($this->attributes['module'] ?? '', true);

        return (json_last_error() === JSON_ERROR_NONE && is_array($decoded))
            ? $decoded
            : null;
    }


    public function un_active_products(){
        return $this->belongsToMany(Product::class, "product_group_product", "group_product_id", "product_id");
    }

    public function products_price(){
        return $this->hasMany(GroupPrice::class, "group_product_id");
    }
}
