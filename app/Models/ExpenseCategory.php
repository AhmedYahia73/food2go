<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status'
    ];
    
    public function translations()
    {
        return $this->morphMany(TranslationTbl::class, 'translatable');
    }
}
