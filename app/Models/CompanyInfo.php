<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyInfo extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'address',
        'logo',
        'fav_icon',
        'time_zone',
        'time_format',
        'currency_id', 
        'currency_position',
        'copy_right', 
        'country',
    ];
    protected $appends = ['logo_link', 'fav_icon_link'];

    public function getLogoLinkAttribute(){
        return url('storage/' . $this->attributes['logo']);
    }
    public function getFavIconLinkAttribute(){ 
        return url('storage/' . $this->attributes['fav_icon']);
    }
    public function currency(){
        return $this->belongsTo(Currency::class);
    }
}
