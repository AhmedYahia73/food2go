<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\ExtraProduct;

class OptionProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'product_id',
        'variation_id',
        'status',
        'points',
    ];
    protected $appends = ['taxes'];

    public function group_price(){
        return $this->hasMany(GroupOptionPrice::class, "option_id");
    }

    public function group_product_status(){
        return $this->belongsToMany(GroupProduct::class, "product_group_product", "product_id", "group_product_id", 'product_id', 'id');
    }

    public function option_pricing(){
        return $this->hasMany(OptionPricing::class, 'option_id');
    }

    public function extra(){
        return $this->hasMany(ExtraProduct::class, 'option_id');
    }

    public function extra_pricing(){
        return $this->hasMany(ExtraPricing::class, 'option_id');
    }

    public function product(){
        return $this->belongsTo(Product::class, 'product_id');
    }
    
    public function getTaxesAttribute(){
        return Setting::where('name', 'tax')
        ->orderByDesc('id')
        ->first();
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
