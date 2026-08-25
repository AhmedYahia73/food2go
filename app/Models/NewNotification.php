<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class NewNotification extends Model
{
    use HasFactory, LogChanges;
    
    protected $fillable = [
        'title',
        'notification',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
