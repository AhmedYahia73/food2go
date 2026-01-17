<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxModule extends Model
{

    protected $fillable = [
        'tax',
        'status',
    ];

    public function module(){
        return $this->hasMany(TaxModuleBranch::class, 'tax_module_id');
    }
}
