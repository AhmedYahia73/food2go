<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\DealTimes;
use App\Models\User;

class Deal extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 
        'image', 
        'description', 
        'price', 
        'status',
        'daily',
        'start_date',
        'end_date',
    ];

    public function times(){
        return $this->hasMany(DealTimes::class, 'deal_id');
    }

    public function deal_customer(){
        return $this->belongsToMany(User::class, 'deal_user')
        ->withPivot(['ref_number', 'status', 'id']);;
    }
}
