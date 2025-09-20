<?php

namespace App\Http\Controllers\api\cashier\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Cashier;
use App\Models\Order;

class HomeController extends Controller
{
    public function __construct(private Cashier $cashier,
    private Order $order){}

    public function view(Request $request){
        // https://bcknd.food2go.online/cashier/home
        $cashiers = $this->cashier
        ->where('branch_id', $request->user()->branch_id)
        ->where('cashier_active', 0)
        ->where('status', 1)
        ->get();

        return response()->json([
            'cashiers' => $cashiers
        ]);
    }

    public function active_cashier(Request $request, $id){
        // https://bcknd.food2go.online/cashier/home/active_cashier/{id}
        $this->cashier
        ->where('id', $id)
        ->update([
            'cashier_active' => 1
        ]);

        return response()->json([
            'success' => 'You activate cashier success'
        ]);
    }

    public function cashier_data(Request $request){
        $orders = $this->order
        ->where('pos', 1)
        ->where('order_active', 1)
        ->where('cashier_man_id', $request->user()->id)
        ->get();
        $take_away = $orders->where('order_type', 'take_away')
        ->where('take_away_status', '!=', 'pick_up')
        ->values();
        $dine_in = $orders->where('order_type', 'dine_in')
        ->values();
        $delivery = $orders->where('order_type', 'delivery')
        ->values();
        // if($orders->order_type == 'take_away'){
        //     $orders->take_away_status = 'preparing';
        // }
        // $order->save();

        return response()->json([
            'take_away' => $take_away,
            'dine_in' => $dine_in,
            'delivery' => $delivery,
            'take_away_count' => $take_away->count(),
            'dine_in_count' => $dine_in->count(),
            'delivery_count' => $delivery->count(),
        ]);
    }
}
