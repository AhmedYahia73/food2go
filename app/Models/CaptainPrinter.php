<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Tax;

class CaptainPrinter extends Model
{
    use HasFactory;

    protected $fillable = [
        'print_name', 
        'print_port', 
        'print_ip', 
        'print_type', 
        'captain_order_id', 
    ]; 
}
