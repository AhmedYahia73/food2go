<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class GroupExtraPrice extends Model
{
    protected $fillable = [
        'extra_id',
        'group_product_id',
        'price', 
    ];

    public function extra(){
        return $this->belongsTo(ExtraProduct::class, "extra_id");
    }

    public function group(){
        return $this->belongsTo(GroupProduct::class, "group_product_id");
    }

    public function getIdAttribute($value){
        return (int) $value;
    }
}
