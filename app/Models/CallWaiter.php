<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class CallWaiter extends Model
{ 
    use HasFactory, LogChanges;

    protected $fillable = [
        'table_id',
        'status',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
