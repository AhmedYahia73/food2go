<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'roles',
        'cashier_man_id',
    ];
}
