<?php

namespace App\Http\Controllers\api\admin\inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseStock;
use App\Models\PurchaseStore;
use App\Models\PurchaseProduct;
use App\Models\PurchaseCategory; 
use App\Models\InventoryProductHistory;
use App\Models\Purchase;
use App\Models\InventoryList;
use App\Models\PurchaseWasted;

class InventoryProductController extends Controller
{
    public function __construct(private PurchaseStore $stores,
    private PurchaseStock $stocks, private PurchaseProduct $products,
    private PurchaseCategory $categories, 
    private InventoryProductHistory $product_history, private Purchase $purchase
    , private InventoryList $inventory_list){}

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
 
    public function current_inventory_history(Request $request){
        $inventory_list = $this->inventory_list
        ->orderByDesc("id")
        ->whereHas("products")
        ->where("status", "current")
        ->with("store")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "has_shortage" => $item?->products?->filter(function ($item) {
                    return $item['quantity'] != $item['actual_quantity'];
                })->count() > 0  ? true : false,
                "store" => $item?->store?->name,
                "product_num" => $item->product_num,
                "total_quantity" => $item->total_quantity,
                "cost" => $item->cost,
                "date" => $item->created_at,
                "status" => $item->status,
            ];
        });

        return response()->json([
            "inventory_list" => $inventory_list, 
        ]);
    } 

    public function inventory_history(Request $request){
        $inventory_list = $this->inventory_list
        ->orderByDesc("id")
        ->whereHas("products")
        ->where("status", "final")
        ->with("store")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "store" => $item?->store?->name,
                "product_num" => $item->product_num,
                "total_quantity" => $item->total_quantity,
                "cost" => $item->cost,
                "date" => $item->created_at,
                "status" => $item->status,
            ];
        });

        return response()->json([
            "inventory_list" => $inventory_list, 
        ]);
    } 

    public function create_inventory(Request $request){
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:purchase_stores,id',
            "type" => 'required|in:partial,full',
            'products' => 'array',
            'products.*' => 'required|exists:purchase_products,id',
            'categories' => 'array',
            'categories.*' => 'required|exists:purchase_categories,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $inventory = InventoryList::
        create([
            "store_id" => $request->store_id,
        ]);
        $all_quantity = 0;
        $items_count = 0;
        $products = collect([]);
        if($request->products && count($request->products) > 0){
            $products = $request->products;
            $products = PurchaseProduct::
            whereIn("id", $products)
            ->get();
        }
        elseif($request->categories && count($request->categories) > 0){
            $products = PurchaseProduct::
            whereIn("category_id", $request->categories)
            ->get();
        }
        foreach ($products as $item) {
            $stock = $this->stocks
            ->where("product_id", $item->id)
            ->first();
            $stock_quintity = $stock?->quantity ?? 0;
            $all_quantity += $stock_quintity;
            
            $product_inventory = InventoryProductHistory::
            create([
                'category_id' => $item->category_id,
                'product_id' => $item->id,
                'inventory_id' => $inventory->id,
                'quantity' => $stock_quintity, 
                'actual_quantity' => $stock_quintity, 
                'inability' => 0,
                'cost' => 0,
            ]);
        }  
        $inventory->product_num = ++$items_count;;
        $inventory->total_quantity = $all_quantity;
        $inventory->save();

        return response()->json([
            "stocks" => $stock ?? [],
            "inventory" => $inventory,
        ]);
    }

    public function open_inventory(Request $request, $id){
        $products = InventoryProductHistory::
        where("inventory_id", $id)
        ->with("category", "product")
        ->get()
        ->map(function($item){
            return [
                "category" => $item?->category?->name,
                "product" => $item?->product?->name,
                "quantity" => $item?->quantity, 
                "inability" => $item?->inability,
                "cost" => $item?->cost,
            ];
        }); 

        return response()->json([
            "products" => $products
        ]);
    }

    public function modify_products(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'products' => 'required|array',
            'products.*.id' => 'required|exists:purchase_products,id',
            'products.*.quantity' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
 
        InventoryList::
        where("id", $id)
        ->update([
            "status" => "final"
        ]);
        foreach ($request->products as $item) {
            $cost = 0;
            $stock = $this->stocks
            ->where("product_id", $item['id'])
            ->first();
            $stock_quintity = $stock->quantity ?? 0;
            $last_purchase_amount = 0;
            $purchase = $this->purchase
            ->where('store_id', $stock->store_id)
            ->where('product_id', $item['id'])
            ->orderByDesc("id")
            ->get();
            $purchase_arr = [];
            $total_quantity = $stock_quintity - $item['quantity'];
            $item_quantity = $stock_quintity - $item['quantity'];
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
            InventoryProductHistory::
            where("inventory_id", $id)
            ->where("product_id", $item['id'])
            ->update([
                'quantity' => $item['quantity'],
                'cost' => $cost,
                'inability' => $item_quantity,
            ]); 
        }

        return response()->json([
            "success" => "You update stoks success"
        ]);
    }

    public function inability_list(Request $request, $id){
        $inability = InventoryProductHistory::
        where("inventory_id", $id)
        ->whereColumn('actual_quantity', '>', 'quantity')
        ->with("category", "product")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "category" => $item?->category?->name,
                "product" => $item?->product?->name,
                "quantity" => $item->quantity,
                "actual_quantity" => $item->actual_quantity,
                "inability" => $item->inability,
                "cost" => $item->cost,
            ];
        });

        return response()->json([
            "shourtage_list" => $inability
        ]);
    }

    public function wested(Request $request){
        $validator = Validator::make($request->all(), [
            'reason' => ["required"],
            'inabilities' => ['required', "array"],
            "inabilities.*" => ['required', "exists:inventory_product_histories,id"], 
            'store_id' => ['required', 'exists:purchase_stores,id'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        } 
 
        foreach ($request->inabilities as $item) {
            $inventory_product = InventoryProductHistory::
            where("id", $item)
            ->first();
            $wested = $inventory_product->inability; 
            $inventory_product->update([
                "actual_quantity" => $inventory_product->quantity
            ]);
        
            $material_stock = $this->stocks
            ->where('product_id', $item)
            ->where('store_id', $request->store_id)
            ->first();
            $westedRequest['status'] = 'approve';
            $wested = PurchaseWasted::
            create([ 
                'store_id' => $request->store_id, 
                'quantity' => $wested,
                'status' => "approve",
                'product_id' => $inventory_product->product_id,
                'category_id' => $inventory_product->category_id,
                'reason' => $request->reason,
            ]);
            
            if(empty($material_stock)){
                $this->stocks
                ->create([
                    'category_id' => $inventory_product->category_id,
                    'product_id' => $inventory_product->product_id,
                    'store_id' => $request->store_id,
                    'quantity' => $inventory_product->quantity,
                    'actual_quantity' => $inventory_product->quantity,
                ]);
            }
            else{
                $material_stock->quantity = $inventory_product->quantity;
                $material_stock->actual_quantity = $inventory_product->quantity;
                $material_stock->save();
            } 
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }
    // public function inventory_history(Request $request){
    //     $inventory_list = $this->inventory_list
    //     ->orderByDesc("id")
    //     ->with("store")
    //     ->get()
    //     ->map(function($item){
    //         return [
    //             "id" => $item->id,
    //             "store" => $item?->store?->name,
    //             "product_num" => $item->product_num,
    //             "total_quantity" => $item->total_quantity,
    //             "cost" => $item->cost,
    //             "date" => $item->created_at,
    //         ];
    //     });

    //     return response()->json([
    //         "inventory_list" => $inventory_list, 
    //     ]);
    // }

    // public function create_inventeory(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'store_id' => 'required|exists:purchase_stores,id',
    //         "type" => 'required|in:partial,full',
    //         'products' => 'array',
    //         'products.*' => 'exists:purchase_products,id', 
    //         'category_products' => 'array',
    //         'category_products.*' => 'exists:purchase_categories,id', 
    //     ]);
    //     if ($validator->fails()) { // if Validate Make Error Return Message Error
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ],400);
    //     }

    //     $stocks = $this->stocks
    //     ->where("store_id", $request->store_id)
    //     ->with("category", "product");
    //     if($request->products && $request->type == "partial"){
    //         $stocks = $stocks
    //         ->whereIn("product_id", $request->products);
    //     }
    //     elseif($request->category_products && $request->type == "partial"){
    //         $stocks = $stocks
    //         ->whereIn("category_id", $request->category_products);
    //     }
    //     $stocks = $stocks
    //     ->get() 
    //     ->map(function($item){
    //         return [
    //             "id" => $item->id,
    //             "quantity" => $item->quantity,
    //             "actual_quantity" => $item->actual_quantity,
    //             "category" => $item?->category?->name,
    //             "product" => $item?->product?->name,
    //             "unit" => $item?->unit?->name,
    //             "inability" => $item->inability,
    //         ];
    //     });

    //     return response()->json([
    //         "stocks" => $stocks,
    //         "material_count" => $stocks->sum('quantity'),
    //     ]);
    // }

    // public function modify_stocks(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required',
    //         'stocks' => 'required|array',
    //         'stocks.*.id' => 'required|exists:purchase_stocks,id',
    //         'stocks.*.quantity' => 'required|numeric',
    //     ]);
    //     if ($validator->fails()) { // if Validate Make Error Return Message Error
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ],400);
    //     }

    //     foreach ($request->stocks as $item) {
    //         $cost = 0;
    //         $stock = $this->stocks
    //         ->where("id", $item['id'])
    //         ->first();
            
    //         $last_purchase_amount = 0;
    //         $purchase = $this->purchase
    //         ->where('store_id', $stock->store_id)
    //         ->where('product_id', $stock->product_id)
    //         ->orderByDesc("id")
    //         ->get();
    //         $purchase_arr = [];
    //         $stock_quintity = $stock->quintity;
    //         $total_quantity = $item['quantity'] - $stock_quintity;
    //         foreach ($purchase as $element) {
    //             $last_purchase_amount = $element->quintity;
    //             $purchase_arr[] = $element;
    //             if($element->quintity >= $stock_quintity){
    //                 break;
    //             }
    //             $stock_quintity -= $element->quintity;
    //         } 
    //         foreach ($purchase_arr as $key => $element) {
    //             $cost_item = $element->total_coast / $element->quintity;
    //             if($key == 0 && count($purchase_arr) > 1){
    //                 $cost += $cost_item * $last_purchase_amount;
    //             }
	// 			elseif(count($purchase_arr) == $key + 1){ 
    //                 $cost += $cost_item * $total_quantity;
	// 			}
    //             else{
    //                 $cost += $cost_item * $element->quintity; 
    //             }
	// 			$total_quantity -= $element->quintity;
    //         } 
    //         $this->product_history
    //         ->create([
    //             'product_id' => $stock->product_id,
    //             'cost' => $cost,
    //             'quantity_from' => $stock->quantity,
    //             'quantity_to' => $item['quantity'],
    //             'inability' => $item['quantity'] - $stock->quantity,
    //             'inventory_id' => $inventory->id,
    //         ]);
    //         $stock->update([
    //             "quantity" => $item['quantity'],
    //             "actual_quantity" => $item['quantity'],
    //         ]);
    //     }

    //     return response()->json([
    //         "success" => "You update stoks success"
    //     ]);
    // }

    // public function modify_actual(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'stocks' => 'required|array',
    //         'stocks.*.id' => 'required|exists:purchase_stocks,id',
    //         'stocks.*.actual_quantity' => 'required|numeric',
    //     ]);
    //     if ($validator->fails()) { // if Validate Make Error Return Message Error
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ],400);
    //     }

    //     foreach ($request->stocks as $item) {
    //         $this->stocks
    //         ->where("id", $item['id'])
    //         ->update([
    //             "actual_quantity" => $item['actual_quantity'],
    //         ]);
    //     }

    //     return response()->json([
    //         "success" => "You update actual quantity success"
    //     ]);
    // }
}
