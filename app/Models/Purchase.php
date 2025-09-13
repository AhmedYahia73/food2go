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
}
