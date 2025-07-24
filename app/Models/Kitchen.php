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
        'status',
    ];

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function products(){
        return $this->hasMany(Product::class);
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
