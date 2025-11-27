<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleFinancial extends Model
{
    protected $fillable = [
        'module_id',
        'financial_id',
        'amount',
        'cashier_id',
        'cahier_man_id',
    ];

    public function financial(){
        return $this->belongsTo(FinantiolAcounting::class, 'financial_id');
    }
}
