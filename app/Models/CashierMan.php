<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class CashierMan extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'my_id',
        'image',
        'branch_id',
        'take_away',
        'dine_in',
        'car_slow',
        'take_away',
        'shift_number',
        'user_name',
        'password',
        'status',
    ];
    protected $appends = ['role', 'image_link'];

    public function getImageLinkAttribute(){
        return url('storage/' . $this->attributes['image']);
    }

    public function getRoleAttribute(){
        return 'cashier';
    }
    
    public function getmodulesAttribute($data){
        if (json_decode($data)) {
            return json_decode($data);
        }
        return [$data];
    }

    public function roles(){
        return $this->hasMany(CashierRole::class, 'cashier_man_id');
    }

    public function branch(){
        return $this->belongsTo(Branch::class);
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
