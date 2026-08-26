<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class ExpenseList extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'category_id',
        'name',
        'status',
    ];
    
    public function translations()
    {
        return $this->morphMany(TranslationTbl::class, 'translatable');
    }

    public function category(){
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function getIdAttribute($value){
        return (int) $value;
    }
}
