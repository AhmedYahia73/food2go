<?php

namespace App\Http\Controllers\api\admin\payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class PaymentController extends Controller
{
    public function __construct(private Order $orders){}

    public function pending(){
        // https://bcknd.food2go.online/admin/payment/pending
        $orders_details = $this->orders
        ->whereNull('status')
        ->with('user')
        ->get();

        return response()->json([
            'orders' => $orders_details
        ]);
    }

    public function history(){
        // https://bcknd.food2go.online/admin/payment/history
        $orders_details = $this->orders
        ->whereNotNull('status')
        ->with('user')
        ->get();

        return response()->json([
            'orders' => $orders_details
        ]);
    }
}
