<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinancialHistory extends Model
{
    protected $fillable = [
        'from_financial_id',
        'to_financial_id',
        'admin_id',
        'amount'
    ];

    public function from_financial(){
        return $this->belongsTo(FinantiolAcounting::class, 'from_financial_id');
    }

    public function to_financial(){
        return $this->belongsTo(FinantiolAcounting::class, 'to_financial_id');
    }

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
