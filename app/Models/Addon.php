<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Tax;

class Addon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'tax_id',
        'quantity_add',
    ];

    public function tax(){
        return $this->belongsTo(Tax::class, 'tax_id');
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
