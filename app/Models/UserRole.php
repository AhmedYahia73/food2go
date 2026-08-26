<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class UserRole extends Model
{ 
    use HasFactory, LogChanges;

    protected $fillable = [
        'user_position_id',
        'role',
        'action',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
