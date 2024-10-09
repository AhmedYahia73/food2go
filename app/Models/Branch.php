<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Branch extends Authenticatable
{ 
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'name',
        'address',
        'email',
        'phone',
        'image',
        'cover_image',
        'password',
        'food_preparion_time',
        'latitude',
        'longitude',
        'coverage',
        'status',
        'email_verified_at',
    ];
    protected $appends = ['role'];

    public function getRoleAttribute(){
        return 'branch';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
