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
        'group_id'
    ];

    public function group_price(){
        return $this->hasMany(GroupExtraPrice::class, 'extra_id');
    }

    public function parent_extra(){
        return $this->belongsTo(ExtraProduct::class, 'extra_id');
    }

    public function group(){
        return $this->belongsTo(Group::class, 'group_id');
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
    
    public function option(){
        return $this->belongsTo(OptionProduct::class, 'option_id');
    }
    
    public function variation(){
        return $this->belongsTo(VariationProduct::class, 'variation_id');
    }

    public function scopeWithLocale($query, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        return $query->with(['translations' => function ($query) use ($locale) {
            $query->where('locale', $locale);
        }]);
    }
}
