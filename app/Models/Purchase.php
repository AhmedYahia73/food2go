<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'product_id',
        'admin_id', 
        'store_id',
        'total_coast',
        'quintity',
        'receipt',
        'date',
    ];
    protected $appends = ['receipt_link'];

    public function getReceiptLinkAttribute(){
        if(isset($this->attributes['receipt'])){
            return url('storage/' . $this->attributes['receipt']);
        }
        return null;
    }

    public function category(){
        return $this->belongsTo(PurchaseCategory::class, 'category_id');
    }

    public function product(){
        return $this->belongsTo(PurchaseProduct::class, 'product_id');
    }

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function store(){
        return $this->belongsTo(PurchaseStore::class, 'store_id');
    }
}
