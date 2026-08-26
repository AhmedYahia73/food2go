<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class TimeSittings extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'from',
        'hours',
        'minutes',
        'branch_id',
    ];


    public function getIdAttribute($value){
        return (int) $value;
    }
    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
