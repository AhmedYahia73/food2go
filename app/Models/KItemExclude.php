<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KItemExclude extends Model
{
    protected $fillable = [
        'kitchen_item_id',
        'exclude_id',
    ];

    public function exclude(){
        return $this->belongsTo(ExcludeProduct::class, "exclude_id");
    }
}
