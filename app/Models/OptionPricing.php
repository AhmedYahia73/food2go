<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionPricing extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'option_id',
        'branch_id',
        'price',
    ];
}
