<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'admin_id',
        'from_status',
        'to_status',
    ];
    protected $appends = ['date'];

    public function getDateAttribute(){
        return $this->created_at;
    }

    public function admin(){
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
