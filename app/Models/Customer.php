<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class Customer extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'name',
        'phone',
    ];

    public function addresses(){
        return $this->hasMany(Address::class, 'customer_id');
    }

    public function getIdAttribute($value){
        return (int) $value;
    }
}
