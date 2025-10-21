<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDue extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'order_id',
        'cashier_id',
        'amount',
    ];

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function cashier(){
        return $this->belongsTo(CashierMan::class, 'cashier_id');
    } 

    public function order(){
        return $this->belongsTo(Order::class, 'order_id');
    } 
}
