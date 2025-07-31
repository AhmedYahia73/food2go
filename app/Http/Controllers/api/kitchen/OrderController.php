<?php

namespace App\Http\Controllers\api\kitchen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\KitchenOrder;

class OrderController extends Controller
{
    public function __construct(private KitchenOrder $kitchen_order){}

    public function kitchen_orders(Request $request){
        $kitchen_order = $this->kitchen_order
        ->get();

        return response()->json([
            'kitchen_order' => $kitchen_order
        ]);
    }
}
