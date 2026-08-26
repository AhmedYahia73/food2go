<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

use App\Models\Tax;

class Addon extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'name',
        'price',
        'tax_id',
        'quantity_add',
    ];
    protected $appends = ['taxes'];

    public function tax(){
        return $this->belongsTo(Tax::class, 'tax_id');
    }

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function categories(){
        return $this->belongsToMany(Category::class, 'category_addon', 'addon_id', 'category_id');
    }

    public function getTaxesAttribute(){
        return Setting::where('name', 'tax')
        ->orderByDesc("created_at")
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
