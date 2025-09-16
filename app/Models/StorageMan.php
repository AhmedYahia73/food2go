<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class StorageMan extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'user_name',
        'phone',
        'password',
        'store_id',
        'image',
        'status',
    ];
    protected $appends = ['role', 'image_link'];

    public function getRoleAttribute(){
        return 'store_man';
    }

    public function store(){
        return $this->belongsTo(PurchaseStore::class, 'stora_id');
    }

    public function getImageLinkAttribute(){
        if(isset($this->attributes['image'])){
            return url('storage/' . $this->attributes['image']);
        }
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
