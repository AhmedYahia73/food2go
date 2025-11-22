<?php

namespace App\Http\Controllers\api\admin\delivery;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class SinglePageDeliveryController extends Controller
{
    public function __construct(private Order $ordersModel){}

    public function orders(Request $request){
        // $orders = $this->ordersModel
        // ->where("order_type", "delivery")
        // ->where("")
    }
}
