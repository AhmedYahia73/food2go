<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPaidDebt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'cashier_id',
        'admin_id',
        'amount',
    ];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function cashier(){
        return $this->belongsTo(CashierMan::class, 'cashier_id');
    } 

    public function financial(){
        return $this->belongsToMany(FinantiolAcounting::class, 'user_debt_financials', 'user_debt_id', 'financial_id')
        ->withPivot("amount");
    } 
}
