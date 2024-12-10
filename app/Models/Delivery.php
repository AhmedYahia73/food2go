<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Delivery extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'f_name',
        'l_name',
        'identity_type',
        'identity_number',
        'email',
        'phone',
        'image',
        'identity_image',
        'password',
        'branch_id',
        'status',
        'email_verified_at',
        'phone_status',
        'chat_status',
    ];
    protected $appends = ['role', 'image_link', 'identity_image_link'];

    public function orders_items(){
        return $this->hasMany(Order::class, 'delivery_id');
    }

    public function getRoleAttribute(){
        return 'delivery';
    }

    public function getIdentityImageLinkAttribute(){
        return url('storage/' . $this->attributes['identity_image']);
    }

    public function branch(){
        return $this->belongsTo(Branch::class);
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
}
