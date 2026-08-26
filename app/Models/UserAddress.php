<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits\LogChanges;

class UserAddress extends Pivot
{
    use LogChanges;

    protected $table = 'user_address';

    // Enable auto-incrementing ID on the pivot model so LogChanges can get it
    public $incrementing = true;
    public $keyType = 'string';
    protected $guarded = [];

    public function getIdAttribute($value){
        return (int) $value;
    }
}
