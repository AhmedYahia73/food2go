<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class ExtraPricing extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'price',
        'product_id',
        'variation_id',
        'extra_id',
        'option_id',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
    
    public function extra(){
        return $this->belongsTo(ExtraProduct::class, 'extra_id');
    }
    
    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }
}
