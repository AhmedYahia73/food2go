<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class DealTimes extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'deal_id',
        'day',
        'from',
        'to',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
