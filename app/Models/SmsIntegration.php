<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsIntegration extends Model
{
    use HasFactory;

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
