<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManufaturingRecipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'manufaturing_id',
        'material_id',
        'quantity',
    ];
}
