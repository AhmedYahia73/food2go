<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseCategory extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'category_id', 
    ];

    public function category(){
        return $this->belongsTo(PurchaseCategory::class, 'category_id');
    }
}
