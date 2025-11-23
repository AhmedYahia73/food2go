<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModulePayment extends Model
{ 
    protected $fillable = [
        'group_product_id',
        'amount',
    ];

    public function module_financials(){
        return $this->hasMany(ModuleFinancial::class, 'module_id');
    }
}
