<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id',
        'branch_id',
        'price',
        'zone',
        'status',
    ];

    public function city(){
        return $this->belongsTo(City::class, 'city_id');
    }

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
