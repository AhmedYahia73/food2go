<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    public function translations()
    {
        return $this->morphMany(TranslationTbl::class, 'translatable');
    }
}
