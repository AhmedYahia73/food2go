<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Models\Order;
use App\Models\Product;
use App\Models\Deal;
use App\Models\Address;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'f_name',
        'l_name',
        'email',
        'phone',
        'image',
        'password',
        'wallet',
        'status',
        'email_verified_at',
        'points',
        'bio',
        'code',
    ];
    protected $appends = ['role', 'orders_count', 'image_link', 'name', 'type'];

    public function getNameAttribute(){
        return $this->attributes['f_name'] . ' ' . $this->attributes['l_name'];
    }

    public function getTypeAttribute(){
        return 'user';
    }

    public function address(){
        return $this->belongsToMany(Address::class ,'user_address');
    }

    public function getRoleAttribute(){
        return 'customer';
    }

    public function getImageLinkAttribute(){
        return url('storage/' . $this->attributes['image']);
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

    public function orders(){
        return $this->hasMany(Order::class, 'user_id');
    }

    public function getOrdersCountAttribute(){
        return $this->hasMany(Order::class, 'user_id')
        ->count();
    }

    public function favourite_product(){
        return $this->belongsToMany(Product::class, 'favourit_product');
    }

    public function deals(){
        return $this->belongsToMany(Deal::class, 'deal_user')
        ->withPivot(['ref_number', 'created_at']);;
    }

    public function coupons(){
        return $this->belongsToMany(Coupon::class, 'coupon_user');
    }
}
