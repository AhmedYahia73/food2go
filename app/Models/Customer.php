<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
    ];
    protected $appends = ['type'];

    public function getTypeAttribute(){
        return 'customer';
    }

    protected function casts(): array
    {
        return [
            'id' => 'string',
        ];
    }

}
