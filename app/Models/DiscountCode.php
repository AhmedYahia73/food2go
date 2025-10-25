<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_name',
        'discount',
        'usage_number',
        'number_codes',
        'start',
        'end',
    ];

    public function codes(){
        return $this->hasMany(GeneratedDiscountCode::class, 'discount_code_id');
    }
}
