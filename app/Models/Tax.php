<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class Tax extends Model
{ 
    use HasFactory, LogChanges;

    protected $fillable = [
        'name',
        'type',
        'amount',
    ];

    public function tax_module(){
        return $this->hasMany(TaxModule::class, 'tax_id');
    }
    
}
