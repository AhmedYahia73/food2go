<?php

namespace App\Models;

use App\Traits\LogChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoice extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'purchase_id',
        'payment',
        'due',
    ];
}
