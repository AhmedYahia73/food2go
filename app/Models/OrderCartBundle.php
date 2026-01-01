<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderCartBundle extends Model
{
    protected $fillable = [
        "order_cart_id",
        "bundle_id",
        "count",
    ];

    public function bundle(){
        return $this->belongsTo(Bundle::class, 'bundle_id');
    }
}
