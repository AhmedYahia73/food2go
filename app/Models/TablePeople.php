<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TablePeople extends Model
{
    protected $fillable = [
        'table_id',
        'count',
        'is_active',
        'shift_number',
    ];
}
