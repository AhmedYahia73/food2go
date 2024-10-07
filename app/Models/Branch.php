<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{ 
    use HasFactory;

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

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
}
