<?php

namespace App\Http\Controllers\api\admin\pos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class PosOrderController extends Controller
{
    public function __construct(private Order $orders){}

    public function pos_orders(){
        $orders = $this->orders
        ->where('pos', 1)
        ->get();

        return response()->json([
            'orders' => $orders
        ]);
    }
}
