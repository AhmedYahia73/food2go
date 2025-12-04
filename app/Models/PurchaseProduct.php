<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseProduct extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'category_id',
        'min_stock',
    ];

    public function category(){
        return $this->belongsTo(PurchaseCategory::class, 'category_id');
    }
}
