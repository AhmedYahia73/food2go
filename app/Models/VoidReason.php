<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class VoidReason extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'void_reason',
        'status',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
