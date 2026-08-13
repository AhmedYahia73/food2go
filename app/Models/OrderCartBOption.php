<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class OrderCartBOption extends Model
{
    protected $fillable = [
        "order_cart_id",
        "variation_bundle_id", 
        "option_id",
    ]; 

    public function option(){
        return $this->belongsTo(OptionProduct::class, 'option_id');
    }
}
