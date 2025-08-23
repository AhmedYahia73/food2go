<?php

namespace App\Http\Controllers\api\waiter\orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\KitchenOrder;
use App\Models\OrderCart;

class OrdersController extends Controller
{
    public function __construct(private OrderCart $order_carts,
    private KitchenOrder $kitchen_order){}

    public function view(Request $request){
        $locations = $request->user()?->locations?->pluck('id') ?? collect([]);
        $orders = $this->order_carts
        ->where('prepration_status', 'done')
        ->whereHas('table', function($query) use($locations){
            $query->where('location_id', $locations);
        })
        ->get();

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function status(Request $request, $id){
        $this->order_carts
        ->where('id', $id)
        ->update([
            'prepration_status' => 'pick_up'
        ]);

        return respone()->json([
            'success' => 'You update status success'
        ]);
    }
}
