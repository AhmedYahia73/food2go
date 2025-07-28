<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kitchen extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'password',
        'branch_id',
        'type',
        'status',
    ];

    public function products(){
        return $this->belongsToMany(Product::class, 'kitchen_products');
    }

    public function category(){
        return $this->belongsToMany(Category::class, 'kitchen_products');
    }

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    protected $hidden = [
        'password', 
    ];

    protected function casts(): array
    {
        return [ 
            'password' => 'hashed',
        ];
    }
}
