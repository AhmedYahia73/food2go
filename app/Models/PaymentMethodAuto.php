<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodAuto extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'callback',
        'api_key',
        'iframe_id',
        'integration_id', 
        'Hmac',
        'payment_method_id', 
    ];
}
