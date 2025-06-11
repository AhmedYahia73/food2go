<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_id',
        'price',
        'min',
        'max',
        'option_id',
        'variation_id',
    ];

    public function parent_extra(){
        return $this->belongsTo(ExtraProduct::class, 'extra_id');
    }
    
    public function translations()
    {
        return $this->morphMany(TranslationTbl::class, 'translatable');
    }

    public function pricing(){
        return $this->hasMany(ExtraPricing::class, 'extra_id');
    }
    
    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopeWithLocale($query, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        return $query->with(['translations' => function ($query) use ($locale) {
            $query->where('locale', $locale);
        }]);
    }
}
