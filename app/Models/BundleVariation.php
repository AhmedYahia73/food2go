<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundleVariation extends Model
{
    protected $fillable = [
        'bundle_id',
        'variation_id',
        'product_id',
    ];

    public function variation(){
        return $this->belongsTo(VariationProduct::class, 'variation_id');
    }

    public function options(){
        return $this->hasMany(BundleOption::class, 'variation_id');
    }
}
