<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class CashierRole extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'roles',
        'cashier_man_id',
    ];
}
