<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseFinancial extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'financial_id',
        'amount',
    ];
}
