<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenueImage extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'image',
        'status',
    ];
    protected $appends = ['image_link'];

    public function getImageLinkAttribute(){
        return url('storage/' . $this->attributes['image']);
    }
}
