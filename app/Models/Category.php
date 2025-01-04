<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Product;
use App\Models\Addon;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'image',
        'banner_image',
        'category_id',
        'status',
        'priority',
        'active',
    ];
    protected $appends = ['image_link', 'banner_link'];

    public function getImageLinkAttribute(){
        return url('storage/' . $this->attributes['image']);
    }

    public function getBannerLinkAttribute(){
        return url('storage/' . $this->attributes['banner_image']);
    }

    public function sub_categories(){
        return $this->hasMany(Category::class, 'category_id')
        ->orderBy('priority');
    }

    public function parent_categories(){
        return $this->belongsTo(Category::class, 'category_id')
        ->orderBy('priority');
    }

    public function parent(){
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function products(){
        return $this->hasMany(Product::class, 'sub_category_id');
    }

    public function category_products(){
        return $this->hasMany(Product::class, 'category_id');
    }

    public function addons(){
        return $this->belongsToMany(Addon::class, 'category_addon', 'category_id', 'addon_id');
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
