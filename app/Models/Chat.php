<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'delivery_id',
        'user_id',
        'message',
        'order_id',
        'user_sender',
    ];

    public function delivery(){
        return $this->belongsTo(Delivery::class, 'delivery_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(){
        return $this->belongsTo(Order::class, 'delivery_id');
    }
}
