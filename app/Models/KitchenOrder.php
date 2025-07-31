<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order', 
        'table_id',
        'kitchen_id',
        'type',
        'order_id',
        'read_status',
    ];

    public function getorderAttribute(){
        return json_decode($this->attributes['order']);
    }

    public function table(){
        return $this->belongsTo(CafeTable::class, 'table_id');
    }

    public function order_data(){
        return $this->belongsTo(Order::class, 'order_id');
    }
}
