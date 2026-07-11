<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceFees extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'title',
        'type',
        'amount',
        'module',
        'online_type',
        'modules',
        "all_products"
    ];

    protected $hidden = array('pivot');
    
    public function branches(){
        return $this->belongsToMany(Branch::class, 'service_fees_branch', 'fees_id', 'branch_id');
    }
    
    public function products(){
        return $this->belongsToMany(Product::class, 'service_fees_product', 'service_fees_id', 'product_id');
    }
    
    protected function casts(): array
    {
        return [
            'modules' => 'array',
        ];
    }
}
