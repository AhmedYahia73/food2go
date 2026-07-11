<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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

    protected static function booted(): void
    {
        static::addGlobalScope('active_period', function (Builder $builder) {
            $today = date('Y-m-d');
            $builder->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            });
        });
    }
    
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
