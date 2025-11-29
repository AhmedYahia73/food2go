<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupOptionPrice extends Model
{
    protected $fillable = [
        'option_id',
        'group_product_id',
        'price', 
    ];

    public function option(){
        return $this->belongsTo(OptionProduct::class, "option_id");
    }

    public function group(){
        return $this->belongsTo(GroupProduct::class, "group_product_id");
    }
}
