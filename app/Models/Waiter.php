<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Waiter extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'branch_id',
        'user_name', 
        'password', 
        'fcm_token', 
    ];
    protected $appends = ['role'];

    public function getRoleAttribute(){
        return 'waiter';
    }

    public function locations(){
        return $this->belongsToMany(CafeLocation::class, 'waiter_location', 'waiter_id', 'cafe_location_id');
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
