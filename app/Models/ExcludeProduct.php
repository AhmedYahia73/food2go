<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExcludeProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_id'
    ];
    
    public function translations()
    {
        return $this->morphMany(TranslationTbl::class, 'translatable');
    }
}
