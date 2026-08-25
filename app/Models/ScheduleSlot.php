<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class ScheduleSlot extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'name',
    ];

    public function translations()
    {
        return $this->morphMany(TranslationTbl::class, 'translatable');
    }

    public function getIdAttribute($value){
        return (int) $value;
    }
}
