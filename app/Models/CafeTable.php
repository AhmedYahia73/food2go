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
    protected $appends = ['qr_code_link'];

    public function getQrCodeLinkAttribute(){
        if ($this->qr_code) {
            return url('storage' . $this->qr_code);
        }
    }

    public function branch(){
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function location(){
        return $this->belongsTo(CafeLocation::class, 'location_id');
    }
}
