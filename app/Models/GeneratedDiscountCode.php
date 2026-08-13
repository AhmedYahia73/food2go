<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class GeneratedDiscountCode extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'code',
        'discount_code_id',
        'usage',
    ];

    public function group(){
        return $this->belongsTo(DiscountCode::class, 'discount_code_id');
    }
}
