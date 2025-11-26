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

    public function setModuleAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['module'] = json_encode($value, JSON_UNESCAPED_UNICODE);
        } 
        else if ($value === null) {
            $this->attributes['module'] = null;
        } 
        else {
            $this->attributes['module'] = $value;
        }
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
