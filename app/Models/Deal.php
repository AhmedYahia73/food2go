<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\DealTimes;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 
        'image', 
        'description', 
        'price', 
    ];

    public function times(){
        return $this->hasMany(DealTimes::class, 'deal_id');
    }
}
