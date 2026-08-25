<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class Currency extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'currancy_name',
        'currancy_symbol',
        'currancy_code',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
