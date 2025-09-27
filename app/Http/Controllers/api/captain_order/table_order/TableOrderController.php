<?php

namespace App\Http\Controllers\api\captain_order\table_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\OrderCart;
use App\Models\CafeTable;

class TableOrderController extends Controller
{
    public function __construct(private OrderCart $order_cart,
    private CafeTable $cafe_table){}

    public function merge_table(Request $request){
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
            'merge_tables_ids' => 'required|array',
            'merge_tables_ids.*' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $this->cafe_table
        ->whereIn('id', $request->merge_tables_ids)
        ->update([
            'is_merge' => 1,
            'main_table_id' => $request->table_id,
        ]);

        return response()->json([
            'success' => 'You merge table success'
        ]);
    }

    public function split_table(Request $request){
        $validator = Validator::make($request->all(), [
            'table_id' => 'required|exists:cafe_tables,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $this->cafe_table
        ->where('id', $request->table_id)
        ->update([
            'is_merge' => 0,
            'main_table_id' => null,
        ]);

        return response()->json([
            'success' => 'You split table success'
        ]);
    }
}
