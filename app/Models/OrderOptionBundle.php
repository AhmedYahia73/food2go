<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class OrderOptionBundle extends Model
{
    protected $fillable = [
        "order_bundle_id",
        "variation_id",
        "order_bundle_p_id",
        "option_id", 
    ];

    public function option(){
        return $this->belongsTo(OptionProduct::class, 'option_id');
    }
}
