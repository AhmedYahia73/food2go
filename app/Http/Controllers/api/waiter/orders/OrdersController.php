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
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'notes' => $item->notes,
                'table' => $item?->table?->table_number,
                'location' => $item?->table?->location?->name, 
            ];
        });

        return response()->json([
            'orders' => $orders,
        ]);
    }

    public function order(Request $request, $id){
        $orders = $this->order_carts
        ->where('id', $id)
        ->first();
        $cart = collect($orders->cart)
        ->map(function($item){
            $item = collect($item);
            $extras = collect($item->extras);
            return [
                'extras' 
            ];
        });

        return response()->json([
            'id' => $orders->id,
            'notes' => $orders->notes,
            'table' => $orders?->table?->table_number,
            'location' => $orders?->table?->location?->name,
            'cart' => $orders->cart
        ]);
    }

    public function status(Request $request, $id){
        $this->order_carts
        ->where('id', $id)
        ->update([
            'prepration_status' => 'pick_up'
        ]);

        return response()->json([
            'success' => 'You update status success'
        ]);
    }
}
