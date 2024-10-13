<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\ExtraProduct;
use App\Models\OptionProduct;

class VariationProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'min',
        'max',
        'required',
        'product_id'
    ];

    public function extra(){
        return $this->hasMany(ExtraProduct::class, 'variation_id');
    }

    public function options(){
        return $this->hasMany(OptionProduct::class, 'variation_id');
    }
}
