<?php

namespace App\Http\Controllers\api\admin\inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseStock;
use App\Models\PurchaseStore;

class InventoryProductController extends Controller
{
    public function __construct(private PurchaseStore $stores,
    private PurchaseStock $stocks){}

    public function lists(Request $request){
        $stores = $this->stores
        ->select("id", "name")
        ->get();

        return response()->json([
            "stores" => $stores
        ]);
    }

    public function view(Request $request){
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:purchase_stores,id', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $stocks = $this->stocks
        ->where("store_id", $request->store_id)
        ->with("category", "material")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "quantity" => $item->quantity,
                "actual_quantity" => $item->actual_quantity,
                "category" => $item?->category?->name,
                "product" => $item?->product?->name,
                "unit" => $item?->unit?->name,
            ];
        });
    }

    public function modify_stocks(Request $request){
        $validator = Validator::make($request->all(), [
            'stocks' => 'required|array',
            'stocks.*.id' => 'required|exists:purchase_stocks,id',
            'stocks.*.quantity' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        foreach ($request->stocks as $item) {
            $this->stocks
            ->where("id", $item['id'])
            ->update([
                "quantity" => $item['quantity'],
            ]);
        }

        return response()->json([
            "success" => "You update stoks success"
        ]);
    }

    public function modify_actual(Request $request){
        $validator = Validator::make($request->all(), [
            'stocks' => 'required|array',
            'stocks.*.id' => 'required|exists:purchase_stocks,id',
            'stocks.*.actual_quantity' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        foreach ($request->stocks as $item) {
            $this->stocks
            ->where("id", $item['id'])
            ->update([
                "actual_quantity" => $item['actual_quantity'],
            ]);
        }

        return response()->json([
            "success" => "You update actual quantity success"
        ]);
    }
}
