<?php

namespace App\Http\Controllers\api\admin\inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Events\NotificationEvent;

use App\Models\MaterialStock;
use App\Models\PurchaseStore;
use App\Models\Material;
use App\Models\MaterialCategory; 
use App\Models\InventoryMaterialHistory;
use App\Models\Purchase;
use App\Models\InventoryList;
use App\Models\Notification;
use App\Models\PurchaseWasted;

class InventoryMaterialController extends Controller
{
    public function __construct(private PurchaseStore $stores,
    private MaterialStock $stocks, private Material $materials,
    private MaterialCategory $categories,
    private InventoryMaterialHistory $materials_history,
    private Purchase $purchase, private InventoryList $inventory_list){}

    public function lists(Request $request){
        $stores = $this->stores
        ->select("id", "name")
        ->get();
        $materials = $this->materials
        ->select("name", "id", "category_id")
        ->get();
        $categories = $this->categories
        ->select("id", "name")
        ->get();

        return response()->json([
            "stores" => $stores,
            "materials" => $materials,
            "categories" => $categories,
        ]);
    }

    public function inventory_history(Request $request){
        $inventory_list = $this->inventory_list
        ->orderByDesc("created_at")
        ->with("store")
        ->where("status", "final")
        ->where("type", "material")
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

    public function current_inventory_history(Request $request){
        $inventory_list = $this->inventory_list
        ->orderByDesc("created_at")
        ->with("store", "materials")
        ->where("type", "material")
        ->where("status", "current")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "has_shortage" => $item?->materials?->filter(function ($item) {
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

    /**
     * Calculate valuation metrics for raw material stock using the exact same logic as MaterialController::material_stock.
     *
     * @param int $storeId
     * @param int $materialId
     * @param float $quantity
     * @return array ['unit_cost' => float, 'total_cost' => float, 'last_cost' => float]
     */
    private function calculateMaterialStockCost(int $storeId, int $materialId, float $quantity): array
    {
        $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('purchases', 'material_id');

        if ($hasColumn) {
            $purchases = Purchase::where('store_id', $storeId)
                ->where('material_id', $materialId)
                ->orderByDesc('created_at')
                ->get();

            if ($purchases->isEmpty()) {
                $purchases = Purchase::where('material_id', $materialId)
                    ->orderByDesc('created_at')
                    ->get();
            }
        } else {
            $purchases = Purchase::where('store_id', $storeId)
                ->whereHas('materials', function ($q) use ($materialId) {
                    $q->where('material_id', $materialId);
                })
                ->with(['materials' => function ($q) use ($materialId) {
                    $q->where('material_id', $materialId);
                }])
                ->orderByDesc('created_at')
                ->get();

            if ($purchases->isEmpty()) {
                $purchases = Purchase::whereHas('materials', function ($q) use ($materialId) {
                    $q->where('material_id', $materialId);
                })
                ->with(['materials' => function ($q) use ($materialId) {
                    $q->where('material_id', $materialId);
                }])
                ->orderByDesc('created_at')
                ->get();
            }
        }

        $lastPurchase = $purchases->first();
        $lastMaterialItem = ($lastPurchase && $lastPurchase->relationLoaded('materials'))
            ? $lastPurchase->materials->first()
            : null;

        $lastQty = $lastMaterialItem && $lastMaterialItem->count > 0
            ? (float)$lastMaterialItem->count
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

                $materialItem = $purchase->relationLoaded('materials') ? $purchase->materials->first() : null;
                $purchaseQty = $materialItem && $materialItem->count > 0
                    ? (float)$materialItem->count
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
            'materials' => 'array',
            'materials.*' => 'required|exists:materials,id',
            'category_materials' => 'array',
            'category_materials.*' => 'required|exists:material_categories,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $storeId = (int)$request->store_id;

        $inventory = InventoryList::create([
            "store_id" => $storeId,
            "type" => 'material',
        ]);

        $materials = collect([]);
        if($request->materials && count($request->materials) > 0){
            $materials = Material::whereIn("id", $request->materials)->get();
        }
        elseif($request->category_materials && count($request->category_materials) > 0){
            $materials = Material::whereIn("category_id", $request->category_materials)->get();
        }
        elseif($request->type == "full"){
            $materials = Material::all();
        }

        // 1. جلب المخزون لكل المواد في هذا المتجر بشكل دقيق
        $storeStocks = MaterialStock::where("store_id", $storeId)
            ->pluck('quantity', 'material_id');

        $all_quantity = 0;
        $all_cost = 0;
        $items_count = 0;

        foreach ($materials as $item) {
            $stock_quintity = (float)($storeStocks[$item->id] ?? 0);
            $all_quantity += $stock_quintity;
            $items_count++;

            // 2. حساب التكلفة بناءً على رصيد المخزون الفعلي للمادة بهذا المتجر
            $costMetrics = $this->calculateMaterialStockCost($storeId, (int)$item->id, $stock_quintity);
            $item_cost = $costMetrics['total_cost'];
            $all_cost += $item_cost;

            InventoryMaterialHistory::create([
                'category_id' => $item->category_id,
                'material_id' => $item->id,
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
        $materials = InventoryMaterialHistory::
        where("inventory_id", $id)
        ->with("category", "material")
        ->get()
        ->map(function($item){
            return [
                "category" => $item?->category?->name,
                "material" => $item?->material?->name,
                "material_id" => $item?->material?->id,
                "quantity" => $item?->quantity, 
                "actual_quantity" => $item?->actual_quantity,
                "inability" => $item?->inability,
                "cost" => $item?->cost,
            ];
        }); 

        return response()->json([
            "materials" => $materials
        ]);
    }

    public function modify_materials(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'materials' => 'required|array',
            'materials.*.id' => 'required|exists:materials,id',
            'materials.*.actual_quantity' => 'nullable|numeric',
            'materials.*.quantity' => 'nullable|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $arr_items = [];
 
        $InventoryList = InventoryList::
        where("id", $id)
        ->with("store.branches")
        ->first();
        $storeId = (int)$InventoryList?->store_id;
        $store = $InventoryList?->store;
        $storeName = $store?->name ?? 'المخزن';
        $branches_ids = $store?->branches?->pluck("id")->toArray() ?? [];

        foreach ($request->materials as $item) {
            $material_item = Material::
            where("id", $item['id'])
            ->first();
            $stock = MaterialStock::
            where("material_id", $item['id'])
            ->where("store_id", $storeId)
            ->first();
            $stock_quintity = $stock->quantity ?? 0; 
            $qty = isset($item['actual_quantity']) ? (float)$item['actual_quantity'] : (float)($item['quantity'] ?? 0);
            $total_quantity = $qty - $stock_quintity;
            $item_quantity = $qty - $stock_quintity;
 
            // حساب التكلفة الدقيقة للكمية الفعلية للمادة
            $costMetrics = $this->calculateMaterialStockCost($storeId, (int)$item['id'], $qty);
            $cost = $costMetrics['total_cost'];
 
            InventoryMaterialHistory::
            where("inventory_id", $id)
            ->where("material_id", $item['id'])
            ->update([
                //'quantity' => $item['quantity'],
                'actual_quantity' => $qty,
                'cost' => $cost,
                'inability' => $item_quantity,
            ]); 
            $one_item = InventoryMaterialHistory::
            where("inventory_id", $id)
            ->where("material_id", $item['id'])
            ->first();
            $arr_items[] = 
             [
                "id" => $one_item?->id ?? null,
                "quantity" => $one_item?->quantity ?? null,
                "actual_quantity" => $one_item?->actual_quantity ?? null,
                "inability" => $one_item?->inability ?? null,
                "cost" => $one_item?->cost ?? null,
                "date" => $one_item?->created_at ?? null,
                "category" => $one_item?->category?->name ?? null,
                "material" => $one_item?->material?->name ?? null,
            ];
            if(!empty($stock)){
                $stock->quantity = $qty;
                $stock->actual_quantity = $qty;
                $stock->save();
            }
            else{
                $stock = MaterialStock:: 
                create([
                    "category_id" => $material_item?->category_id,
                    "material_id" => $item['id'], 
                    "store_id" => $storeId,
                    "quantity" => $qty,
                    "actual_quantity" => $qty,
                ]);
            }

            $effectiveStoreName = $storeName ?: ($stock?->store?->name ?? 'المخزن');
            $effectiveBranchesIds = !empty($branches_ids) ? $branches_ids : ($stock?->store?->branches?->pluck("id")->toArray() ?? []);
            $materialName = $material_item?->name ?? $stock?->material?->name ?? ('المادة رقم ' . $item['id']);
            $minStock = (float)($material_item?->min_stock ?? $stock?->material?->min_stock ?? 0);

            $isLowStock = ($minStock > 0 && $stock->quantity <= $minStock) || ($stock->quantity <= 0);

            if($isLowStock){
                $notification = Notification::create([
                    'branch_ids' => $effectiveBranchesIds,
                    'notification' => "المادة الخام {$materialName} وصل للحد الادنى فى المخزن {$effectiveStoreName} الكمية المتاحة الان {$stock->quantity}",
                    'is_read' => false,
                ]);  
                NotificationEvent::dispatch($notification);
            } elseif ($item_quantity != 0) {
                $notification = Notification::create([
                    'branch_ids' => $effectiveBranchesIds,
                    'notification' => "تم تعديل كمية المادة الخام {$materialName} فى المخزن {$effectiveStoreName} الكمية الحالية {$stock->quantity}",
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
        $inability = InventoryMaterialHistory::
        where("inventory_id", $id)
        //->whereColumn('actual_quantity', '>', 'quantity')
        ->with("category", "material")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "category" => $item?->category?->name,
                "material" => $item?->material?->name,
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
            "inabilities.*" => ['required', "exists:inventory_material_histories,id"], 
            'store_id' => ['required', 'exists:purchase_stores,id'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        } 
 
        foreach ($request->inabilities as $item) {
            $inventory_material = InventoryMaterialHistory::
            where("id", $item)
            ->first();
            $wested = $inventory_material->inability; 
            $inventory_material->update([
                "actual_quantity" => $inventory_material->quantity
            ]);
        
            $material_stock = $this->stocks
            ->where('material_id', $item)
            ->where('store_id', $request->store_id)
            ->first();
            $westedRequest['status'] = 'approve';
            $wested = PurchaseWasted::
            create([ 
                'store_id' => $request->store_id, 
                'quantity' => $wested,
                'status' => "approve",
                'material_id' => $inventory_material->material_id,
                'category_material_id' => $inventory_material->category_id,
                'reason' => $request->reason,
            ]);
            
            if(empty($material_stock)){
                $this->stocks
                ->create([
                    'category_id' => $inventory_material->category_id,
                    'material_id' => $inventory_material->material_id,
                    'store_id' => $request->store_id,
                    'quantity' => $inventory_material->quantity,
                    'actual_quantity' => $inventory_material->quantity,
                ]);
            }
            else{
                $material_stock->quantity = $inventory_material->quantity;
                $material_stock->actual_quantity = $inventory_material->quantity;
                $material_stock->save();
            } 
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    // public function modify_stocks(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'stocks' => 'required|array',
    //         'stocks.*.id' => 'required|exists:material_stocks,id',
    //         'stocks.*.quantity' => 'required|numeric',
    //     ]);
    //     if ($validator->fails()) { // if Validate Make Error Return Message Error
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ],400);
    //     }

    //     $inventory = $this->inventory
    //     ->create([
    //         "admin_id" => $request->user()->id,
    //     ]);
    //     foreach ($request->stocks as $item) {
    //         $cost = 0;
    //         $stock = $this->stocks
    //         ->where("id", $item['id'])
    //         ->first();
    //         $stock_quintity = $stock->quintity;
    //         $last_purchase_amount = 0;
    //         $purchase = $this->purchase
    //         ->where('store_id', $stock->store_id)
    //         ->where('material_id', $stock->material_id)
    //         ->orderByDesc("created_at")
    //         ->get();
    //         $purchase_arr = [];
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
    //         $this->materials_history
    //         ->create([
    //             'material_id' => $stock->material_id,
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
    //         'stocks.*.id' => 'required|exists:material_stocks,id',
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

    // public function history(Request $request){
    //     $material_inventory = $this->inventory
    //     ->where("type", "material")
    //     ->with("admin")
    //     ->get()
    //     ->map(function($item){
    //         return [
    //             "id" => $item->id,
    //             "date" => $item->created_at->format("Y-m-d"),
    //             "time" => $item->created_at->format("H:i"),
    //             "admin" => $item?->admin?->name,
    //         ];
    //     });

    //     return response()->json([
    //         "material_inventory" => $material_inventory
    //     ]);
    // }

    // public function history_details(Request $request){

    // }
}
