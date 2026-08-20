<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class CashierGap extends Model
{ 
    use LogChanges;
    
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'amount',
        'cashier_id',
        'cashier_man_id',
        'shift',
    ];
 
    public function cashier(){
        return $this->belongsTo(Cashier::class, 'cashier_id');
    }
 
    public function cashier_man(){
        return $this->belongsTo(CashierMan::class, 'cashier_man_id');
    }
 
    public function shift(){
        return $this->belongsTo(CashierShift::class, 'shift', "shift");
    }
}
