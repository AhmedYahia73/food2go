<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class DeviceToken extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'admin_id',
        'branch_id',
        'token',
    ];
}
