<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountModuleBranch extends Model
{ 
    use HasFactory;
    // module => take_away, dine_in, delivery
    protected $fillable = [
        'discount_module_id',
        'branch_id',
        'module',
    ];

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
