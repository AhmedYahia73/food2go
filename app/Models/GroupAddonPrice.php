<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupAddonPrice extends Model
{
    protected $fillable = [
        'addon_id',
        'group_product_id',
        'price', 
    ];

    public function addon(){
        return $this->belongsTo(Addon::class, "addon_id");
    }

    public function group(){
        return $this->belongsTo(GroupProduct::class, "group_product_id");
    }
}
