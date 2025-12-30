<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BundleOption extends Model
{
    protected $fillable = [
        'bundle_id',
        'variation_id',
        'option_id', 
    ];
}
