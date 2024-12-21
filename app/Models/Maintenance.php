<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Maintenance extends Model
{
    use HasFactory;

    protected $fillable = [
        'all',
        'branch',
        'customer',
        'web',
        'delivery',
        'day',
        'week',
        'until_change',
        'customize',
        'start_date',
        'end_date',
        'status',
    ];
}
