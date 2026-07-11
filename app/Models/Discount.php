<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'amount', 
        "module",
        "start_date",
        "end_date",
    ];
    
    public function products(){
        return $this->belongsToMany(Product::class, "discount_product", "discount_id", "product_id");
    }
    
    public function categories(){
        return $this->belongsToMany(Category::class, "discount_category", "discount_id", "category_id");
    }
    
    protected function casts(): array
    {
        return [
            'module' => 'array',
        ];
    }
}
