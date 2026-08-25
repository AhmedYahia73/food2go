<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class CashierBalance extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'balance',
        'cashier_id',
        'cashier_man_id',
        'shift_number'
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
