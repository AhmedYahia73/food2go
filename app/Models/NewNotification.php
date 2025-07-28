<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewNotification extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'notification',
    ];
}
