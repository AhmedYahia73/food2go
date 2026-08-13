<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class Maintenance extends Model
{
    use HasFactory, LogChanges;

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
