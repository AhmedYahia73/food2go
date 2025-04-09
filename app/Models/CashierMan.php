<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class CashierMan extends Model
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'branch_id',
        'cashier_id',
        'modules',
        'user_name',
        'password',
        'status',
    ];

    public function getmodulesAttribute($data){
        if (json_decode($data)) {
            return json_decode($data);
        }
        return [$data];
    }

    public function branch(){
        return $this->belongsTo(Branch::class);
    }
    
    public function cashier(){
        return $this->belongsTo(Cashier::class);
    }

    protected $hidden = [
        'password',
    ];
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
