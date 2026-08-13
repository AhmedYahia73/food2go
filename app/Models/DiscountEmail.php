<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class DiscountEmail extends Model
{
    protected $fillable = [
        'email',
    ];
}
