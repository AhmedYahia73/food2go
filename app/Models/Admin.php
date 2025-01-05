<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;

use App\Models\UserPosition;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name',
        'identity_type',
        'identity_number',
        'email',
        'phone',
        'image',
        'identity_image',
        'password',
        'user_position_id',
        'status',
        'email_verified_at',
    ];
    protected $appends = ['role', 'image_link', 'identity_image_link'];

    public function getRoleAttribute(){
        return 'admin';
    }

    public function getImageLinkAttribute(){
        return url('storage/' . $this->attributes['image']);
    }

    public function getIdentityImageLinkAttribute(){
        return url('storage/' . $this->attributes['identity_image']);
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

    public function user_positions(){
        return $this->belongsTo(UserPosition::class, 'user_position_id');
    }
}
