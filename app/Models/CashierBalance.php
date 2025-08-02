<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'balance',
        'cashier_id',
        'cashier_man_id',
        'cashier_num'
    ];
}
