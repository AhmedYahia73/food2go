<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class KItemExtra extends Model
{
    protected $fillable = [
        'kitchen_item_id',
        'extra_id',
    ];

    public function extra(){
        return $this->belongsTo(ExtraProduct::class, "extra_id");
    }

    public function getIdAttribute($value){
        return (int) $value;
    }
}
