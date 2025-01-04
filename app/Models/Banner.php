<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Category;
use App\Models\Product;
use App\Models\Deal;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'order',
        'category_id',
        'product_id',
        'deal_id',
        'translation_id',
        'status',
    ];
    protected $appends = ['image_link'];

    public function getImageLinkAttribute(){
        return url('storage/' . $this->attributes['image']);
    }

    public function category_banner(){
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function deal(){
        return $this->belongsTo(Deal::class, 'deal_id');
    }
    
    public function translations(){
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
