<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleFinancial extends Model
{
    protected $fillable = [
        'group_product_id',
        'financial_id',
        'amount',
    ]; 
}
