<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Kitchen extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'password',
        'branch_id',
        'type',
        'print_name',
        'print_type',
        'print_port',
        'print_ip',
        'print_status',
        'preparing_time',
        'status',
    ];

    public function printer(){
        return $this->hasMany(PrinterKitchen::class, "kitchen_id");
    }

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
        'remember_token',
        'pivot', 
    ];

    protected function casts(): array
    {
        return [ 
            'password' => 'hashed',
        ];
    }
}
