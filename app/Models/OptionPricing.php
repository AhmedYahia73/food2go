<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class OptionPricing extends Model
{ 
    use HasFactory, LogChanges;

    protected $fillable = [
        'option_id',
        'branch_id',
        'price',
    ];
}
