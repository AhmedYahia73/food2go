<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\ExtraProduct;

class OptionProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'product_id',
        'variation_id',
        'status',
        'points',
    ];

    public function extra(){
        return $this->hasMany(ExtraProduct::class, 'option_id');
    }
}
