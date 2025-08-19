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
        'location',
    ];
 
    public function getlocationAttribute(){
        if(!empty($this->attributes['location'])){
            return json_decode($this->attributes['location'], true);
        }
    }

    public function tables(){
        return $this->hasMany(CafeTable::class, 'location_id');
    }

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
