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
        'phone_status',
        'main',
        'block_reason',
    ];
    protected $appends = ['role', 'image_link', 'cover_image_link', 'map'];

    public function translations()
    {
        return $this->morphMany(TranslationTbl::class, 'translatable');
    }

    public function scopeWithLocale($query, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        return $query->with(['translations' => function ($query) use ($locale) {
            $query->where('locale', $locale);
        }]);
    }

    public function getMapAttribute(){
        return "https://www.google.com/maps?q={$this->attributes['latitude']},{$this->attributes['longitude']}";
    }

    public function zone(){
        return $this->hasOne(Zone::class, 'branch_id');
    }
    
    public function getImageLinkAttribute(){
        if(isset($this->attributes['image'])){
            return url('storage/' . $this->attributes['image']);
        }
        return null;
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
