<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class DiscountModule extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'discount',
        'status',
    ];

    public function module(){
        return $this->hasMany(DiscountModuleBranch::class, 'discount_module_id');
    }
}
