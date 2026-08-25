<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class ExtraGroup extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'name',
        'pricing',
        'group_id',
        'min',
        'max',
    ];
    
    public function translations()
    {
        return $this->morphMany(TranslationTbl::class, 'translatable');
    }

    public function getIdAttribute($value){
        return (int) $value;
    }

    public function scopeWithLocale($query, $locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        return $query->with(['translations' => function ($query) use ($locale) {
            $query->where('locale', $locale);
        }]);
    }
}
