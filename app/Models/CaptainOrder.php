<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class CaptainOrder extends Model
{ 
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'branch_id',
        'name',
        'email',
        'phone',
        'password',
    ];

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function locations(){
        return $this->belongsToMany(CafeLocation::class, 'captain_location', 'captain_order_id', 'cafe_location_id');
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
