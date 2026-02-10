<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrinterKitchen extends Model
{
    protected $fillable = [
        'print_name',
        'print_ip',
        'print_status',
        'print_type',
        'print_port', 
        'kitchen_id',
        'module',
    ];

    protected $hidden = [
        'pivot', 
    ];

    protected function casts(): array
    {
        return [
            'module' => 'array',
        ];
    }

    public function group_product(){
        return $this->belongsToMany(GroupProduct::class, "printer_module", "printer_kitchen_id", "group_product_id");
    }

    public function kitchen(){
        return $this->belongsTo(Kitchen::class, "kitchen_id");
    }
}
