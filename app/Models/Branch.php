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
        'city_id',
        'password',
        'food_preparion_time',
        'latitude',
        'longitude',
        'coverage',
        'status',
        'email_verified_at',
        'main'
    ];
    protected $appends = ['role', 'image_link', 'cover_image_link'];

    public function getImageLinkAttribute(){
        return url('storage/' . $this->attributes['image']);
    }

    public function getCoverImageLinkAttribute(){
        return url('storage/' . $this->attributes['cover_image']);
    }

    public function getRoleAttribute(){
        return 'branch';
    }

    public function city(){
        return $this->belongsTo(City::class, 'city_id');
    }

    public function orders(){
        return $this->hasMany(Order::class, 'branch_id');
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
