<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'branch_ids',
        'notification',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'branch_ids' => 'array',
        ];
    }
}
