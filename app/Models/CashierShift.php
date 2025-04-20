<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'shift',
        'start_time',
        'end_time',
        'cashier_man_id',
    ];

    public function cashier_man(){
        return $this->belongsTo(CashierMan::class ,'cashier_man_id');
    }
}
