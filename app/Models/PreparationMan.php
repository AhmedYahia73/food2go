<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreparationMan extends Model
{  
    protected $fillable = [
        'name',
        'password',
        'branch_id',
        'status',
    ];
}
