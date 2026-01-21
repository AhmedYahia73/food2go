<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CafeTable extends Model
{
    use HasFactory;
    protected $fillable = [
        'table_number',
        'location_id',
        'branch_id',
        'capacity',
        'qr_code',
        'current_status',
        'occupied',
        'status',
        'is_merge',
        'main_table_id',
        'preparation_num',
        'start_timer',
        'order',
        'captain_id',
    ];
    protected $appends = ['qr_code_link'];

    public function getQrCodeLinkAttribute(){
        if ($this->qr_code) {
            return url('storage/' . $this->qr_code);
        }
    }

    public function call_payment(){
        return $this->hasMany(CheckoutRequest::class, 'table_id');
    }

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function location(){
        return $this->belongsTo(CafeLocation::class, 'location_id');
    }

    public function main_table(){
        return $this->belongsTo(CafeTable::class, 'main_table_id');
    }

    public function sub_table(){
        return $this->hasMany(CafeTable::class, 'main_table_id');
    }

    public function order_cart(){
        return $this->hasMany(OrderCart::class, 'table_id');
    }
}
