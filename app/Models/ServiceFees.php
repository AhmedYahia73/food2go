<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceFees extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
    ];

    public function branch(){
        return $this->belongsToMany(Branch::class, 'service_fees_branch', 'fees_id', 'branch_id');
    }
}
