<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleDue extends Model
{ 
    protected $fillable = [
        'branch_id',
        'due',
    ];  
}
