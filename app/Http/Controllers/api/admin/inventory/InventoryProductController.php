<?php

namespace App\Http\Controllers\api\admin\inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Events\NotificationEvent;

use App\Models\PurchaseStock;
use App\Models\PurchaseStore;
use App\Models\PurchaseProduct;
use App\Models\PurchaseCategory; 
use App\Models\InventoryProductHistory;
use App\Models\Purchase;
use App\Models\InventoryList;
use App\Models\PurchaseWasted;
use App\Models\Notification;

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
 
    public function update_inventory_status(Request $request, $id){
        $inventory_list = $this->inventory_list
        ->where("id", $id)
        ->update([
            "status" => "final"
        ]);

        return response()->json([
            "success" => "final", 
        ]);
    } 
 
    public function current_inventory_history(Request $request){
        $inventory_list = $this->inventory_list
        ->orderByDesc("created_at")
        ->where("type", "product")
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
                "store_id" => $item?->store?->id,
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
        ->orderByDesc("created_at")
        ->where("type", "product")
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

    /**
     * Calculate valuation metrics for product stock using the exact same logic as PurchaseProductController::product_stock.
     *
     * @param int $storeId
     * @param int $productId
     * @param float $quantity
     * @return array ['unit_cost' => float, 'total_cost' => float, 'last_cost' => float]
     */
    private function calculateProductStockCost(int $storeId, int $productId, float $quantity): array
    {
        $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('purchases', 'product_id');

        if ($hasColumn) {
            $purchases = Purchase::where('store_id', $storeId)
                ->where('product_id', $productId)
                ->orderByDesc('created_at')
                ->get();

            if ($purchases->isEmpty()) {
                $purchases = Purchase::where('product_id', $productId)
                    ->orderByDesc('created_at')
                    ->get();
            }
        } else {
            $purchases = Purchase::where('store_id', $storeId)
                ->whereHas('products', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                })
                ->with(['products' => function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                }])
                ->orderByDesc('created_at')
                ->get();

            if ($purchases->isEmpty()) {
                $purchases = Purchase::whereHas('products', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                })
                ->with(['products' => function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                }])
                ->orderByDesc('created_at')
                ->get();
            }
        }

        $lastPurchase = $purchases->first();
        $lastProductItem = ($lastPurchase && $lastPurchase->relationLoaded('products'))
            ? $lastPurchase->products->first()
            : null;

        $lastQty = $lastProductItem && $lastProductItem->count > 0
            ? (float)$lastProductItem->count
            : (float)($lastPurchase?->quintity ?? 0);

        $lastCost = ($lastPurchase && $lastQty > 0)
            ? ($lastPurchase->total_coast / $lastQty)
            : 0;

        $unitCost = 0;
        if ($quantity > 0 && $purchases->isNotEmpty()) {
            $remainingStockToValuate = $quantity;
            $totalValueOfStock = 0;

            foreach ($purchases as $purchase) {
                if ($remainingStockToValuate <= 0) break;

                $productItem = $purchase->relationLoaded('products') ? $purchase->products->first() : null;
                $purchaseQty = $productItem && $productItem->count > 0
                    ? (float)$productItem->count
                    : (float)$purchase->quintity;

                if ($purchaseQty <= 0) continue;

                $unitPrice = $purchase->total_coast / $purchaseQty;
                $qtyToTakeFromPurchase = min($remainingStockToValuate, $purchaseQty);
                $totalValueOfStock += ($qtyToTakeFromPurchase * $unitPrice);
                $remainingStockToValuate -= $qtyToTakeFromPurchase;
            }

            $actualValuatedQty = $quantity - $remainingStockToValuate;
            if ($actualValuatedQty > 0) {
                $unitCost = $totalValueOfStock / $actualValuatedQty;
            }
        }

        return [
            'unit_cost'  => round($unitCost, 2),
            'total_cost' => round($unitCost * $quantity, 2),
            'last_cost'  => round($lastCost, 2),
        ];
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

        $storeId = (int)$request->store_id;

        $inventory = InventoryList::create([
            "store_id" => $storeId,
            "type" => 'product',
        ]);

        $products = collect([]);
        if($request->products && count($request->products) > 0){
            $products = PurchaseProduct::whereIn("id", $request->products)->get();
        }
        elseif($request->categories && count($request->categories) > 0){
            $products = PurchaseProduct::whereIn("category_id", $request->categories)->get();
        }
        elseif($request->type == "full"){
            $products = PurchaseProduct::all();
        }

        // 1. جلب المخزون لكل المنتجات في هذا المتجر بشكل دقيق
        $storeStocks = PurchaseStock::where("store_id", $storeId)
            ->pluck('quantity', 'product_id');

        $all_quantity = 0;
        $all_cost = 0;
        $items_count = 0;

        foreach ($products as $item) { 
            $stock_quintity = (float)($storeStocks[$item->id] ?? 0);
            $all_quantity += $stock_quintity;
            $items_count++;

            // 2. حساب التكلفة بناءً على رصيد المخزون الفعلي لهذا المتجر
            $costMetrics = $this->calculateProductStockCost($storeId, (int)$item->id, $stock_quintity);
            $item_cost = $costMetrics['total_cost'];
            $all_cost += $item_cost;
            
            InventoryProductHistory::create([
                'category_id' => $item->category_id,
                'product_id' => $item->id,
                'inventory_id' => $inventory->id,
                'quantity' => $stock_quintity, 
                'actual_quantity' => $stock_quintity, 
                'inability' => 0,
                'cost' => $item_cost,
            ]);
        }  

        $inventory->product_num = $items_count;
        $inventory->total_quantity = $all_quantity;
        $inventory->cost = round($all_cost, 2);
        $inventory->save();

        return response()->json([
            "stocks" => $storeStocks,
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
                "product_id" => $item?->product?->id,
                "quantity" => $item?->quantity, 
                "actual_quantity" => $item?->actual_quantity, 
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
            'products.*.actual_quantity' => 'nullable|numeric', 
            'products.*.quantity' => 'nullable|numeric', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
  
        $InventoryList = InventoryList::
        where("id", $id)
        ->with("store.branches")
        ->first();
        $arr_items = [];
        $storeId = (int)$InventoryList?->store_id;
        $store = $InventoryList?->store;
        $storeName = $store?->name ?? 'المخزن';
        $branches_ids = $store?->branches?->pluck("id")->toArray() ?? [];

        foreach ($request->products as $item) {
            $product_item = PurchaseProduct::
            where("id", $item['id'])
            ->first();
            $stock = PurchaseStock::
            where("product_id", $item['id'])
            ->where("store_id", $storeId)
            ->first();
            $stock_quintity = $stock->quantity ?? 0;
            $qty = isset($item['actual_quantity']) ? (float)$item['actual_quantity'] : (float)($item['quantity'] ?? 0);
            $total_quantity = $qty - $stock_quintity;
            $item_quantity = $qty - $stock_quintity;
       
            // حساب التكلفة الدقيقة للكمية الفعلية
            $costMetrics = $this->calculateProductStockCost($storeId, (int)$item['id'], $qty);
            $cost = $costMetrics['total_cost'];

            InventoryProductHistory::
            where("inventory_id", $id)
            ->where("product_id", $item['id'])
            ->update([
                //'quantity' => $item['quantity'],
                'actual_quantity' => $qty,
                'cost' => $cost,
                'inability' => $item_quantity,
            ]); 
            $one_item = InventoryProductHistory::
            where("inventory_id", $id)
            ->where("product_id", $item['id'])
            ->orderByDesc("created_at")
            ->first();
            $arr_items[] = [
                "id" => $one_item?->id ?? null,
                "quantity" => $one_item?->quantity ?? null,
                "actual_quantity" => $one_item?->actual_quantity ?? null,
                "inability" => $one_item?->inability ?? null,
                "cost" => $one_item?->cost ?? null,
                "date" => $one_item?->created_at ?? null,
                "category" => $one_item?->category?->name,
                "product" => $one_item?->product?->name,
            ];
            if(!empty($stock)){
                $stock->quantity = $qty;
                $stock->actual_quantity = $qty;
                $stock->save();
            }
            else{
                $stock = PurchaseStock:: 
                create([
                    "category_id" => $product_item?->category_id,
                    "product_id" => $item['id'], 
                    "store_id" => $storeId,
                    "quantity" => $qty,
                    "actual_quantity" => $qty,
                ]);
            }

            $effectiveStoreName = $storeName ?: ($stock?->store?->name ?? 'المخزن');
            $effectiveBranchesIds = !empty($branches_ids) ? $branches_ids : ($stock?->store?->branches?->pluck("id")->toArray() ?? []);
            $productName = $product_item?->name ?? $stock?->product?->name ?? ('المنتج رقم ' . $item['id']);
            $minStock = (float)($product_item?->min_stock ?? $stock?->product?->min_stock ?? 0);

            $isLowStock = ($minStock > 0 && $stock->quantity <= $minStock) || ($stock->quantity <= 0);

            if($isLowStock){
                $notification = Notification::create([
                    'branch_ids' => $effectiveBranchesIds,
                    'notification' => "المنتج {$productName} وصل للحد الادنى فى المخزن {$effectiveStoreName} الكمية المتاحة الان {$stock->quantity}",
                    'is_read' => false,
                ]); 
                NotificationEvent::dispatch($notification);
            } elseif ($item_quantity != 0) {
                $notification = Notification::create([
                    'branch_ids' => $effectiveBranchesIds,
                    'notification' => "تم تعديل كمية المنتج {$productName} فى المخزن {$effectiveStoreName} الكمية الحالية {$stock->quantity}",
                    'is_read' => false,
                ]);
                NotificationEvent::dispatch($notification);
            }
        }

        return response()->json([
            "success" => "You update stoks success",
            "report" => $arr_items,
            "store_name" => $InventoryList?->store?->name,
        ]);
    }

    public function inability_list(Request $request, $id){
        $inability = InventoryProductHistory::
        where("inventory_id", $id)
        //->whereColumn('actual_quantity', '>', 'quantity')
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
    //     ->orderByDesc("created_at")
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
    //         ->orderByDesc("created_at")
    //         ->get();
    //         $purchase_arr = [];
    //         $stock_quintity = $stock->quintity;
    //         $total_quantity = $item['quantity'] - $stock_quintity;
    //         foreach ($purchase as $element) {
    //             $last_purchase_amount = $stock_quintity;
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
