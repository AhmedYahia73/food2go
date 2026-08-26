<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class TablePeople extends Model
{
    protected $fillable = [
        'table_id',
        'count',
        'is_active',
        'shift_number',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
