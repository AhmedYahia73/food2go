<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundleVariation extends Model
{
    protected $fillable = [
        'bundle_id',
        'variation_id',
    ];

    public function variations(){
        return $this->belongsTo(VariationProduct::class, 'variation_id');
    }

    public function options(){
        return $this->hasMany(BundleOption::class, 'variation_id');
    }
}
