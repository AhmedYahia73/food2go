<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class OfferOrder extends Model
{
    use HasFactory, LogChanges;

    protected $fillable = [
        'offer_id',
        'user_id',
        'code',
        'date',
        'status',
    ];


    public function getIdAttribute($value){
        return (int) $value;
    }
    public function offer(){
        return $this->belongsTo(Offer::class, 'offer_id');
    }
}
