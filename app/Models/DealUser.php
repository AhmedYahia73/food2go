<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DealUser extends Model
{
    use HasFactory;
    protected $table = 'deal_user';

    protected $fillable = [
        'deal_id',
        'user_id',
        'ref_number',
        'status',
        'order_status',
    ];

    public function user(){
        return $this->belongsTo(User::class, "user_id");
    }

    public function deal(){
        return $this->belongsTo(Deal::class, "deal_id");
    }

    public function financials(){
        return $this->belongsToMany(FinantiolAcounting::class, "deal_financial", "deal_id", "financial_id")
        ->withPivot("amount");
    }
}
