<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialMedia extends Model
{ 
    protected $fillable = [
        'icon',
        'name',
        'link', 
        'status', 
    ];
    protected $appends = ['icon_link'];

    public function getIconLinkAttribute(){
        if(isset($this->attributes['icon'])){
            return url('storage/' . $this->attributes['icon']);
        }
    }
}
