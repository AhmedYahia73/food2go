<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'branch_id',
        'cashier_active',
        'status',
    ];

    public function branch(){
        return $this->belongsTo(Branch::class, "branch_id");
    }
}
