<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class Purchase extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        "type",
        'admin_id', 
        'store_id',
        'supplier_id',
        'total_coast',
        'payment',
        'due',
        'quintity',
        'receipt',
        'date',
    ];
    protected $appends = ['receipt_link'];


    public function getIdAttribute($value){
        return (int) $value;
    }
    public function financial(){
        return $this->belongsToMany(FinantiolAcounting::class, 'purchase_financials', 'purchase_id', 'financial_id')
        ->withPivot('amount');
    }

    public function getReceiptLinkAttribute(){
        if(isset($this->attributes['receipt'])){
            return url('storage/' . $this->attributes['receipt']);
        }
        return null;
    }

    public function materials(){
        return $this->hasMany(PurchaseMaterial::class, 'purchase_id');
    }

    public function products(){
        return $this->hasMany(PurchaseProductItem::class, 'purchase_id');
    }

    public function invoices(){
        return $this->hasMany(PurchaseInvoice::class, 'purchase_id');
    }

    public function supplier(){
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function store(){
        return $this->belongsTo(PurchaseStore::class, 'store_id');
    }
}
