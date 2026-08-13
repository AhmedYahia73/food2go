<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogChanges;

class ReceiptDesign extends Model
{ 
    protected $fillable = [ 
        'logo',
        'name',
        'address',
        'branch',
        'phone',
        'cashier_name',
        'footer',
        'taxes',
        'services',
        'table_num',
        'preparation_num',
    ];
}
