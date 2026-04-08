<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Geidia extends Model
{

    protected $fillable = [
        'geidea_public_key',
        'api_password',
        'environment',
        'payment_method_id',
    ];
}
