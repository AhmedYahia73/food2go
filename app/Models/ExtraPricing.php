<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraPricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'price',
        'product_id',
        'variation_id',
        'extra_id',
        'option_id',
    ];
    
    public function extra(){
        return $this->belongsTo(ExtraProduct::class, 'extra_id');
    }
    
    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }
}
