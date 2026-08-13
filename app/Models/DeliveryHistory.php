<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class DeliveryHistory extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'order_id',
        'deliveryman_id',
        'time',
        'longitude',
        'latitude',
        'location',
    ];
}
