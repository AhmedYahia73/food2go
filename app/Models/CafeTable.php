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
        'occupied',
        'status',
    ];

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function location(){
        return $this->belongsTo(CafeLocation::class, 'location_id');
    }
}
