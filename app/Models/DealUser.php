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
    ];
}
