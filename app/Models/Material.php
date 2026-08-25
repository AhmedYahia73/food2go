<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class Material extends Model
{ 
    use HasFactory, LogChanges;

    protected $fillable = [
        'name',
        'description',
        'status',
        'category_id',
        'min_stock',
    ];

    public function start_stock(){
        return $this->hasMany(MaterialStore::class, 'product_id');
    }

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function unit(){
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function category(){
        return $this->belongsTo(MaterialCategory::class, 'category_id');
    }

    public function stock(){
        return $this->hasOne(MaterialStock::class, 'material_id');
    }

    public function stock_items(){
        return $this->hasMany(MaterialStock::class, 'material_id');
    }
}
