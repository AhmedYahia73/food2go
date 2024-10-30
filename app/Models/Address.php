<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'zone_id',
        'longitude',
        'latitude',
        'street',
        'building_num',
        'floor_num',
        'apartment',
        'additional_data',
    ];
}
