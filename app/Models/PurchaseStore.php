<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseStore extends Model
{ 
    use HasFactory;

    protected $fillable = [
        'name',
        'location',
        'status',
    ];

    public function branches(){
        return $this->belongsToMany(Branch::class, 'store_branch', 'store_id', 'branch_id');
    }
}
