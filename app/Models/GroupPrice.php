<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupPrice extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'product_id',
        'group_product_id',
        'price', 
    ];

    public function product(){
        return $this->belongsTo(Product::class, "product_id");
    }

    public function group(){
        return $this->belongsTo(GroupProduct::class, "group_product_id");
    }
}
