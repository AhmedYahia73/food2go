<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CafeLocation extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 
        'branch_id', 
    ];

    public function tables(){
        return $this->hasMany(CafeTable::class, 'location_id');
    }
}
