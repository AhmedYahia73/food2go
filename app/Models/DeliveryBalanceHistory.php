<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryBalanceHistory extends Model
{

    protected $fillable = [
        'amount',
        'delivery_id',
        'financial_id',
        'branch_id',
        'cashier_man_id',
        'cashier_id',
    ];

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function delivery(){
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    public function financial_accountigs(){
        return $this->belongsTo(FinantiolAcounting::class, 'financial_id');
    }

    public function cashier_man(){
        return $this->belongsTo(CashierMan::class, 'cashier_man_id');
    }

    public function casheir(){
        return $this->belongsTo(Cashier::class, 'cashier_id');
    }
}
