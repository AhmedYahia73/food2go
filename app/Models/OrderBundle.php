<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderBundle extends Model
{
    protected $fillable = [
        "order_id",
        "bundle_id",
    ];

    public function bundle(){
        return $this->belongsTo(Bundle::class, 'bundle_id');
    }
}
