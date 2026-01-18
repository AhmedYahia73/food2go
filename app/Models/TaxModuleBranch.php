<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxModuleBranch extends Model
{
    // module => take_away, dine_in, delivery
    // all, app, web
    protected $fillable = [
        'tax_module_id',
        'branch_id',
        'module',
        'type',
    ];

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function products(){
        return $this->belongsToMany(Branch::class, 'branch_id');
    }
}
