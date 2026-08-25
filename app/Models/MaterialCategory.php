<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class MaterialCategory extends Model
{ 
    use HasFactory, LogChanges;

    protected $fillable = [
        'name',
        'status',
        'category_id', 
    ];

    public function category(){
        return $this->belongsTo(MaterialCategory::class, 'category_id');
    }

    public function getIdAttribute($value){
        return (int) $value;
    }
}
