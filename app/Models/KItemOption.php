<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class KItemOption extends Model
{
    protected $fillable = [
        'kitchen_variation_id',
        'option_id',
    ];

    public function option(){
        return $this->belongsTo(OptionProduct::class, "option_id");
    }
}
