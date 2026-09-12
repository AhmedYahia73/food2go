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
        "date",
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function financials()
    {
        return $this->hasMany(PurchaseInvoiceFinancial::class, 'purchase_invoice_id');
    }
}
