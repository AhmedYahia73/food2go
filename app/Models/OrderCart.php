<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCart extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'table_id', 
        'cart',
        'date',
        'amount',
        'total_tax',
        'total_discount',
        'notes',
        'prepration_status',
        'order_id',
        'type',
        'captain_id',
    ];

    public function getcartAttribute($data){
        return json_decode($data);
    }

    public function table(){
        return $this->belongsTo(CafeTable::class, 'table_id');
    }
}
