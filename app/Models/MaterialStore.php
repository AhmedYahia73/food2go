<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Tax;

class MaterialStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_stock',
        'cost',
        'unit_id',
        'store_id',
        'product_id',
    ]; 

    public function unit(){
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function store(){
        return $this->belongsTo(PurchaseStore::class, 'store_id');
    }

    public function material(){
        return $this->belongsTo(Material::class, 'product_id');
    } 
}
