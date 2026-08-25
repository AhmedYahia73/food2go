<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class BundleOption extends Model
{
    protected $fillable = [
        'bundle_id',
        'variation_id',
        'option_id', 
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
