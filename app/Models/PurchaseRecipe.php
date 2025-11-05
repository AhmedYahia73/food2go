<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 
        'weight', 
        'status',
    ];

    public function product(){
        return $this->belongsTo(PurchaseProduct::class, 'product_id');
    }  
}
