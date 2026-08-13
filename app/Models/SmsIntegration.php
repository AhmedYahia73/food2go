<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class SmsIntegration extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'user',
        'pwd',
        'senderid',
        'mobileno',
        'msgtext',
        'CountryCode',
        'profileid',
    ];
}
