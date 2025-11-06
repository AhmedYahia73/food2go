<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KItemAddon extends Model
{
    protected $fillable = [
        'kitchen_item_id',
        'addon_id',
    ];

    public function addon(){
        return $this->belongsTo(Addon::class, "addon_id");
    }
}
