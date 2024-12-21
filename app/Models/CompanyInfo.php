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
    ];
    protected $appends = ['logo_link', 'fav_icon_link'];

    public function getLogoLinkAttribute(){
        if (!empty($attributes['logo'])) {
            return url('storage/' . $attributes['logo']);
        }
        else {
            return null;
        }
    }
    public function getFavIconLinkAttribute(){
        if (!empty($attributes['fav_icon_link'])) {
            return url('storage/' . $attributes['fav_icon_link']);
        }
        else {
            return null;
        } 
    }
    public function currency(){
        return $this->belongsTo(Currency::class);
    }
}
