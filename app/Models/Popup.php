<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Popup extends Model
{ 
    protected $fillable = [
        'image_en',
        'image_ar',
        'name_en',
        'name_ar',
        'link',
        'status',
    ];
    protected $appends = ["image_en_link", "image_ar_link"];

    public function getImageEnLinkAttribute(){
        if(isset($this->attributes['image_en'])){
            return url('storage/' . $this->attributes['image_en']);
        }
    }

    public function getImageArLinkAttribute(){
        if(isset($this->attributes['image_ar'])){
            return url('storage/' . $this->attributes['image_ar']);
        }
    }
}
