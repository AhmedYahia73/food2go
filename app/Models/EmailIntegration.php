<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailIntegration extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'integration_password',
    ];
}
