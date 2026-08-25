<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class MenueImage extends Model
{ 
    use HasFactory, LogChanges;

    protected $fillable = [
        'image',
        'status',
    ];
    protected $appends = ['image_link'];


    public function getIdAttribute($value){
        return (int) $value;
    }
    public function getImageLinkAttribute(){
        return url('storage/' . $this->attributes['image']);
    }
}
