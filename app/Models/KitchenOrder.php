<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class KitchenOrder extends Model
{ 
    use HasFactory, LogChanges;
    
    protected $casts = [
        'id' => 'string',
        'table_id' => 'string',
        'cart_id' => 'string',
    ];

    protected $fillable = [
        'table_id',
        'order',
        'kitchen_id',
        'type',
        'cart_id',
        'date'
    ];
    public function table(){
        return $this->belongsTo(CafeTable::class, 'table_id');
    }
}
