<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\DealTimes;
use App\Models\User;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 
        'image', 
        'description', 
        'price', 
        'status',
        'daily',
        'start_date',
        'end_date',
    ];
    protected $appends = ['image_link'];

    public function getImageLinkAttribute(){
        return url('storage/' . $this->attributes['image']);
    }

    public function times(){
        return $this->hasMany(DealTimes::class, 'deal_id');
    }

    public function deal_customer(){
        return $this->belongsToMany(User::class, 'deal_user')
        ->withPivot(['ref_number', 'status', 'id', 'created_at']);
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
