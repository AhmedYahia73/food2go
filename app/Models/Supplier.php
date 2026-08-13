<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'status',
        'balance',
    ];
}
