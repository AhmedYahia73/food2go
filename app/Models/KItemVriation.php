<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KItemVriation extends Model
{
    protected $fillable = [
        'kitchen_item_id',
        'variation_id',
    ];

    public function variation(){
        return $this->belongsTo(VariationProduct::class, "variation_id");
    }

    public function options(){
        return $this->hasMany(OptionProduct::class, "kitchen_variation_id");
    }
}
