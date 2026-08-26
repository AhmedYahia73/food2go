<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class OrderVariationBundle extends Model
{
    protected $fillable = [
        "order_bundle_id",
        "variation_id",
        "order_bundle_p_id",
    ];

    public function order_bundle(){
        return $this->belongsTo(OrderBundle::class, 'order_bundle_id');
    }

    public function variation(){
        return $this->belongsTo(VariationProduct::class, 'variation_id');
    }

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function options(){
        return $this->hasMany(OrderOptionBundle::class, 'option_id');
    }
}
