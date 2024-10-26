<?php

namespace App\Http\Controllers\api\customer\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;
use App\Models\OrderDetails;

class MakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetails $order_details){}

    public function order(){
        
    }
}
