<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class ProductSale extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'product_id',
        'count',
        'date',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
