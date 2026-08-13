<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class SmsBalance extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'package_id',
        'balance',
    ];
}
