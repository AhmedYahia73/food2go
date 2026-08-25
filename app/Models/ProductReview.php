<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

use App\Models\Product;
use App\Models\User;

class ProductReview extends Model
{ 
    use HasFactory, LogChanges;

    protected $fillable = [
        'product_id',
        'user_id',
        'review',
        'rate',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function customer(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
