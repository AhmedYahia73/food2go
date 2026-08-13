<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class ModuleDue extends Model
{
    protected $fillable = [
        'branch_id',
        'due',
    ];  
}
