<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manufaturing extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'product_id',
        'store_id',
        'quantity',
    ];

    public function product(){
        return $this->belongsTo(PurchaseProduct::class, 'product_id');
    }

    public function store(){
        return $this->belongsTo(PurchaseStore::class, 'store_id');
    }

    public function recipe(){
        return $this->hasMany(ManufaturingRecipe::class, 'manufaturing_id');
    }
}
