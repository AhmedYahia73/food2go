<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'deliveryman_id',
        'time',
        'longitude',
        'latitude',
        'location',
    ];
}
