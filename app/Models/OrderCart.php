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
    ];

    public function getcartAttribute($data){
        return json_decode($data);
    }
}
