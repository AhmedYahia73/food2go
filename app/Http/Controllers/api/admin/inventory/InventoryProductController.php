<?php

namespace App\Http\Controllers\api\admin\inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseStock;
use App\Models\PurchaseStore;
use App\Models\PurchaseProduct;
use App\Models\PurchaseCategory;

class InventoryProductController extends Controller
{
    public function __construct(private PurchaseStore $stores,
    private PurchaseStock $stocks, private PurchaseProduct $products,
    private PurchaseCategory $categories,){}

    public function lists(Request $request){
        $stores = $this->stores
        ->select("id", "name")
        ->get();
        $products = $this->products
        ->select("name", "id", "category_id")
        ->get();
        $categories = $this->categories
        ->select("id", "name")
        ->get();

        return response()->json([
            "stores" => $stores,
            "products" => $products,
            "categories" => $categories,
        ]);
    }

    public function view(Request $request){
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:purchase_stores,id',
            'products' => 'required|array',
            'products.*' => 'required|exists:purchase_products,id', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $stocks = $this->stocks
        ->where("store_id", $request->store_id)
        ->whereIn("product_id", $request->products)
        ->with("category", "product")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "quantity" => $item->quantity,
                "actual_quantity" => $item->actual_quantity,
                "category" => $item?->category?->name,
                "product" => $item?->product?->name,
                "unit" => $item?->unit?->name,
                "inability" => $item->inability,
            ];
        });

        return response()->json([
            "stocks" => $stocks
        ]);
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
