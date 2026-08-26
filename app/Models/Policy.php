<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class Policy extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'policy',
        'support',
        'return_policy',
        'delivery_policy',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
