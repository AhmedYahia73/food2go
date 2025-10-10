<?php

namespace App\Http\Controllers\api\admin\table;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\CafeTable;

class TableOrderController extends Controller
{
    public function table_orders(Request $request){
        $tables = CafeTable::
        where("status", 1)
        ->with(["location", "order_cart"])
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "table_number" => $item->table_number,
                "current_status" => $item->current_status,
                "orders_pending" => $item->order_cart
                ? $item->order_cart->whereIn("prepration_status", ["waiting", "preparing"])->count()
                : 0,
                "location" => $item?->location?->name,
                "branch" => $item?->branch?->name,
            ];
        });

        return response()->json([
            "tables" => $tables
        ]);
    }
}
