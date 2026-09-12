<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class PurchaseProductItem extends Model
{
    use HasFactory, LogChanges;

    protected $table = 'purchase_product_items';

    protected $fillable = [
        'purchase_id',
        'category_id',
        'product_id',
        'unit_id',
        'count',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function product()
    {
        return $this->belongsTo(PurchaseProduct::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(PurchaseCategory::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }
}
