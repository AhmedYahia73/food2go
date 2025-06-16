<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainData extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'logo',
        'image_1',
        'image_2',
        'image_3',
        'image_4',
        'image_5',
        'image_6',
        'first_color',
        'second_color',
        'third_color',
    ];
    protected $appends = ['image1_link', 'image2_link', 
    'image3_link', 'image4_link', 'image5_link', 'image6_link'
    , 'logo_link'];

    public function getLogoLinkAttribute(){
        return url('storage/' . $this->logo);
    }

    public function getImage1LinkAttribute(){
        return url('storage/' . $this->image_1);
    }

    public function getImage2LinkAttribute(){
        return url('storage/' . $this->image_2);
    }

    public function getImage3LinkAttribute(){
        return url('storage/' . $this->image_3);
    }

    public function getImage4LinkAttribute(){
        return url('storage/' . $this->image_4);
    }

    public function getImage5LinkAttribute(){
        return url('storage/' . $this->image_5);
    }

    public function getImage6LinkAttribute(){
        return url('storage/' . $this->image_6);
    }
    
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
}
