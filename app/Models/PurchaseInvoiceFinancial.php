<?php

namespace App\Models;

use App\Traits\LogChanges;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseInvoiceFinancial extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'purchase_invoice_id',
        'financial_id',
        'amount',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function invoice()
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }

    public function financial()
    {
        return $this->belongsTo(FinantiolAcounting::class, 'financial_id');
    }
}
