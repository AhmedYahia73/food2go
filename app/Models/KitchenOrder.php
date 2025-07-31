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
    ];

    public function getOrderAttribute(){
        return json_decode($this->order);
    }
}
