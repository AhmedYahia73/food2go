<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StorageMan extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'user_name',
        'phone',
        'password',
        'stora_id',
        'image',
        'status',
    ];
    protected $appends = ['image_link'];

    public function store(){
        return $this->belongsTo(PurchaseStore::class, 'stora_id');
    }

    public function getImageLinkAttribute(){

    }
}
