<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderFinancial extends Model
{ 
    use HasFactory;
    
    protected $fillable = [
        'order_id',
        'financial_id',
        'cashier_id',
        'cashier_man_id',
        'amount',
    ];
}
