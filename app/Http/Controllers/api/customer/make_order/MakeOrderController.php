<?php

namespace App\Http\Controllers\api\customer\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class MakeOrderController extends Controller
{
    public function __construct(private Order $order){}

    public function order(){
        
    }
}
