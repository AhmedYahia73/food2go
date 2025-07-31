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
        ->where('kitchen_id', $request->user()->id)
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'order' => $item->order,
                'table' => $item->table->select('id', 'table_number'),
                'type' => $item->type,
            ];
        });

        return response()->json([
            'kitchen_order' => $kitchen_order
        ]);
    }
}
