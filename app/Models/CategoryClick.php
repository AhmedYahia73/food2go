<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryClick extends Model
{ 
    protected $fillable = [
        'category_id',
        'user_id',
        'app_type',
    ];
}
