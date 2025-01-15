<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Zone;

class Address extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'zone_id',
        'address',
        'street',
        'building_num',
        'floor_num',
        'apartment',
        'additional_data',
        'type',
        'map',
    ];

    public function zone(){
        return $this->belongsTo(Zone::class, 'zone_id');
    }
}
