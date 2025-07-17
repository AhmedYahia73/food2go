<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSittings extends Model
{
    use HasFactory;

    protected $fillable = [
        'from',
        'hours',
        'minutes',
        'branch_id',
    ];

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
