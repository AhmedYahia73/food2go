<?php

namespace App\Http\Controllers\api\admin\inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseStock;
use App\Models\PurchaseStore;
use App\Models\PurchaseProduct;
use App\Models\PurchaseCategory;
use App\Models\InventoryHistory;
use App\Models\InventoryProductHistory;
use App\Models\Purchase;

class InventoryProductController extends Controller
{
    public function __construct(private PurchaseStore $stores,
    private PurchaseStock $stocks, private PurchaseProduct $products,
    private PurchaseCategory $categories, private InventoryHistory $inventory,
    private InventoryProductHistory $product_history, private Purchase $purchase){}

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
            "type" => 'required|in:partial,full',
            'products' => 'array',
            'products.*' => 'exists:purchase_products,id', 
            'category_products' => 'array',
            'category_products.*' => 'exists:purchase_categories,id', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $stocks = $this->stocks
        ->where("store_id", $request->store_id)
        ->with("category", "product");
        if($request->products && $request->type == "partial"){
            $stocks = $stocks
            ->whereIn("product_id", $request->products);
        }
        elseif($request->category_products && $request->type == "partial"){
            $stocks = $stocks
            ->whereIn("category_id", $request->category_products);
        }
        $stocks = $stocks
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
            "stocks" => $stocks,
            "product_count" => $stocks->count(),
        ]);
    }

    public function modify_stocks(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
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
            $cost = 0;
            $stock = $this->stocks
            ->where("id", $item['id'])
            ->first();
            
            $last_purchase_amount = 0;
            $purchase = $this->purchase
            ->where('store_id', $stock->store_id)
            ->where('product_id', $stock->product_id)
            ->orderByDesc("id")
            ->get();
            $purchase_arr = [];
            $stock_quintity = $stock->quintity;
            $total_quantity = $item['quantity'] - $stock_quintity;
            foreach ($purchase as $element) {
                $last_purchase_amount = $element->quintity;
                $purchase_arr[] = $element;
                if($element->quintity >= $stock_quintity){
                    break;
                }
                $stock_quintity -= $element->quintity;
            } 
            foreach ($purchase_arr as $key => $element) {
                $cost_item = $element->total_coast / $element->quintity;
                if($key == 0 && count($purchase_arr) > 1){
                    $cost += $cost_item * $last_purchase_amount;
                }
				elseif(count($purchase_arr) == $key + 1){ 
                    $cost += $cost_item * $total_quantity;
				}
                else{
                    $cost += $cost_item * $element->quintity; 
                }
				$total_quantity -= $element->quintity;
            } 
            $this->product_history
            ->create([
                'product_id' => $stock->product_id,
                'cost' => $cost,
                'quantity_from' => $stock->quantity,
                'quantity_to' => $item['quantity'],
                'inability' => $item['quantity'] - $stock->quantity,
                'inventory_id' => $inventory->id,
            ]);
            $stock->update([
                "quantity" => $item['quantity'],
                "actual_quantity" => $item['quantity'],
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
