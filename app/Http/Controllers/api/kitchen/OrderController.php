<?php

namespace App\Http\Controllers\api\kitchen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\KitchenOrder;
use App\Models\OrderCart;
use App\Models\Order;

class OrderController extends Controller
{
    public function __construct(private KitchenOrder $kitchen_order,
    private OrderCart $order_carts, private Order $order){}

    public function kitchen_orders(Request $request){
        $kitchen_order = $this->kitchen_order
        ->where('kitchen_id', $request->user()->id)
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'order' => $item->order,
                'table' => $item->table,
                'type' => $item->type,
            ];
        });

        return response()->json([
            'kitchen_order' => $kitchen_order
        ]);
    }

    public function done_status(Request $request, $id){
        $kitchen_order = $this->kitchen_order
        ->where('id', $id)
        ->first();
        if($kitchen_order->type == 'dine_in'){
            $this->order_carts
            ->where('table_id', $kitchen_order->table_id)
            ->update([
                'prepration_status' => 'done'
            ]);
        }
        elseif($kitchen_order->type == 'take_away'){
            $this->order
            ->where('id', $kitchen_order->order_id)
            ->update([
                'take_away_status' => 'done'
            ]);
        }
        else{
            $this->order
            ->where('id', $kitchen_order->order_id)
            ->update([
                'delivery_status' => 'done'
            ]);
        }
        $kitchen_order->delete();

        return response()->json([
            'success' => 'You change status success'
        ]);
    }

    public function notification(Request $request){
        $kitchen_order = $this->kitchen_order
        ->where('kitchen_id', $request->user()->id)
        ->where('read_status', false)
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'order' => $item->order,
                'table' => $item->table,
                'type' => $item->type,
            ];
        });

        return response()->json([
            'kitchen_order' => $kitchen_order
        ]);
    }

    public function read_status(Request $request, $id){
        $kitchen_order = $this->kitchen_order
        ->where('id', $id)
        ->update([
            'read_status' => true
        ]);

        return response()->json([
            'success' => 'You update status success'
        ]);
    }
}
