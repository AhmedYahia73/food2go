<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class PurchaseFinancial extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'purchase_id',
        'financial_id',
        'amount',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
