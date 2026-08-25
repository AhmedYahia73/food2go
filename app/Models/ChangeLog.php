<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangeLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'old_payload' => 'array',
        'new_payload' => 'array',
    ];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
