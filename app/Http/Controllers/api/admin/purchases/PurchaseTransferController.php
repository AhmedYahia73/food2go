<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Events\NotificationEvent;

use App\Models\PurchaseTransfer;
use App\Models\Purchase;
use App\Models\PurchaseProduct;
use App\Models\PurchaseCategory;
use App\Models\PurchaseStore;
use App\Models\PurchaseStock;
use App\Models\MaterialStock;
use App\Models\MaterialCategory;
use App\Models\Material;
use App\Models\Unit;

class PurchaseTransferController extends Controller
{
    public function __construct(private PurchaseTransfer $purchases,
    private PurchaseProduct $products, private PurchaseCategory $categories,
    private PurchaseStore $stores, private PurchaseStock $stock,
    private Unit $units, private Material $materials,
    private MaterialCategory $material_categories, 
    private MaterialStock $material_stock){} 


    public function view(Request $request){ 
        $query = $this->purchases
        ->with('category', 'product', 'from_store', 'to_store', 'admin',
        'material', 'category_material');

        if ($request->filled('from_store_id') && !in_array($request->from_store_id, ['all', 'null', 'undefined', ''])) {
            $query->where('from_store_id', $request->from_store_id);
        }

        if ($request->filled('to_store_id') && !in_array($request->to_store_id, ['all', 'null', 'undefined', ''])) {
            $query->where('to_store_id', $request->to_store_id);
        }

        $perPage = (int) $request->get('per_page', 20);
        if ($perPage <= 0) {
            $perPage = 20;
        }

        $paginated = $query
        ->latest('id')
        ->paginate($perPage);

        $paginated->getCollection()->transform(function($item){
            return [
                'id' => $item->id,
                'from_store_id' => $item->from_store_id,
                'to_store_id' => $item->to_store_id,
                'product_id' => $item->product_id,
                'category_id' => $item->category_id,
                'unit' => $item?->unit?->name,
                'unit_id' => $item->unit_id,
                
                'category_material_id' => $item->category_material_id,
                'material_id' => $item->material_id,
                'category_material' => $item?->category_material?->name,
                'material' => $item?->material?->name,
                
                'quintity' => $item->quintity, 
                'to_store' => $item?->to_store?->name,
                'from_store' => $item?->from_store?->name,
                'category' => $item?->category?->name,
                'product' => $item?->product?->name,
                'admin' => $item?->admin?->name,
                'status' => $item->status,
            ];
        });
        $categories = $this->categories
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get();
        $products = $this->products
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get();
        $stores = $this->stores
        ->select('id', 'name')
        ->where('status', 1)
        ->get(); 
        $units = $this->units
        ->select("name", "id")
        ->where("status", 1)
        ->get();
        $materials = $this->materials
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "category_id" => $item->category_id,
            ];
        });
        $material_categories = $this->material_categories
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });

        return response()->json([
            'purchases' => $paginated,
            'pagination' => [
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
            ],
            'categories' => $categories,
            'products' => $products,
            'stores' => $stores, 
            'units' => $units, 
            'material_categories' => $material_categories,
            'materials' => $materials,
        ]);
    }


    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:approve,reject'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $purchases = $this->purchases
        ->where('id', $id)
        ->first();
        $stock = $this->stock
        ->where('category_id', $purchases->category_id)
        ->where('product_id', $purchases->product_id)
        ->where('store_id', $purchases->from_store_id)
        ->first();

        if($request->status == 'approve'){
            if(!empty($request->product_id)){ 
                $from_store = $this->stock
                ->where('store_id', $purchases->from_store_id)
                ->where('product_id', $purchases->product_id)
                ->first();
                $to_store = $this->stock
                ->where('store_id', $purchases->to_store_id)
                ->where('product_id', $purchases->product_id)
                ->first();
                if(empty($from_store)){
                    $this->stock
                    ->create([
                        'category_id' => $purchases->category_id,
                        'product_id' => $purchases->product_id,
                        'store_id' => $purchases->from_store_id,
                        'quantity' => -$purchases->quintity,
                        'actual_quantity' => -$purchases->quintity,
                    ]);
                }
                else{
                    $stock->quantity -= $purchases->quintity;
                    $stock->actual_quantity -= $purchases->quintity;
                    $stock->save();
                    if($stock->quantity < ($stock?->product?->min_stock ?? 0)){
                        $branches_ids = $stock?->store?->branches?->pluck("id")->toArray();
                        $notification = Notification::create([
                            'branch_ids' => $branches_ids,
                            'notification' => "المنتج {$stock?->product?->name} وصل للحد الادنى فى المخزن {$stock?->store?->name} الكمية المتاحة الان {$stock?->quantity}",
                            'is_read' => false,
                        ]); 
                        NotificationEvent::dispatch($notification);
                    }
                }

                if(empty($to_store)){
                    $this->stock
                    ->create([
                        'category_id' => $purchases->category_id,
                        'product_id' => $purchases->product_id,
                        'store_id' => $purchases->to_store_id,
                        'quantity' => $purchases->quintity,
                        'actual_quantity' => $purchases->quintity,
                    ]);
                }
                else{
                    $stock = $this->stock
                    ->where('category_id', $purchases->category_id)
                    ->where('product_id', $purchases->product_id)
                    ->where('store_id', $purchases->to_store_id)
                    ->first();
                    $stock->quantity += $purchases->quintity;
                    $stock->actual_quantity += $purchases->quintity;
                    $stock->save();
                }
                $purchases->status = $request->status;
                $purchases->save();
            }
            else{
                
                $from_store = $this->material_stock
                ->where('store_id', $purchases->from_store_id)
                ->where('material_id', $purchases->material_id)
                ->first();
                $to_store = $this->material_stock
                ->where('store_id', $purchases->to_store_id)
                ->where('material_id', $purchases->material_id)
                ->first();
                if(empty($from_store)){
                    $product = $this->products
                    ->where("id", $purchases->material_id)
                    ->first();
                    $this->material_stock
                    ->create([
                        'category_id' => $purchases->category_id,
                        'material_id' => $purchases->material_id,
                        'store_id' => $purchases->from_store_id,
                        'quantity' => -$purchases->quintity,
                        "actual_quantity" => -$purchases->quintity,
                        'unit_id' => -$product->unit_id,
                    ]);
                }
                else{
                    $material_stock = $this->material_stock 
                    ->where('material_id', $purchases->material_id)
                    ->where('store_id', $purchases->from_store_id)
                    ->first();
                    $material_stock->quantity -= $purchases->quintity;
                    $material_stock->actual_quantity -= $purchases->quintity;
                    $material_stock->save();
                    if($material_stock->quantity < ($material_stock?->material?->min_stock ?? 0)){
                        $branches_ids = $material_stock?->store?->branches?->pluck("id")->toArray();
                        $notification = Notification::create([
                            'branch_ids' => $branches_ids,
                            'notification' => "المادة الخام {$material_stock?->material?->name} وصل للحد الادنى فى المخزن {$material_stock?->store?->name} الكمية المتاحة الان {$material_stock?->quantity}",
                            'is_read' => false,
                        ]); 
                        NotificationEvent::dispatch($notification);
                    }
                }

                if(empty($to_store)){
                    $this->material_stock
                    ->create([
                        'category_id' => $purchases->category_id,
                        'material_id' => $purchases->material_id,
                        'store_id' => $purchases->to_store_id,
                        'quantity' => $purchases->quintity,
                        'actual_quantity' => $purchases->quintity,
                        'unit_id' => $purchases->unit_id,
                    ]);
                }
                else{
                    $material_stock = $this->material_stock
                    ->where('category_id', $purchases->category_id)
                    ->where('material_id', $purchases->material_id)
                    ->where('store_id', $purchases->to_store_id)
                    ->first();
                    $material_stock->quantity += $purchases->quintity;
                    $material_stock->actual_quantity += $purchases->quintity;
                    $material_stock->save();
                } 
            }
        }

        return response()->json([
            'success' => 'You update status success'
        ]);
    }

    /**
     * Calculate cost metrics (cost, last_cost, total_cost, total_last_cost)
     * using the exact same weighted-average valuation logic as
     * MaterialController::material_stock and PurchaseProductController::product_stock.
     *
     * @param int $storeId
     * @param string $idColumn 'material_id' or 'product_id'
     * @param int $itemId
     * @param float $quantity
     * @return array
     */
    private function calculateItemCost(int $storeId, string $idColumn, int $itemId, float $quantity): array
    {
        $hasColumn = \Illuminate\Support\Facades\Schema::hasColumn('purchases', $idColumn);
        $relation = ($idColumn === 'material_id') ? 'materials' : 'products';

        if ($hasColumn) {
            $purchases = Purchase::where('store_id', $storeId)
                ->where($idColumn, $itemId)
                ->orderByDesc('created_at')
                ->get();

            // Fallback: If no direct purchases in this store, check all stores for this item
            if ($purchases->isEmpty()) {
                $purchases = Purchase::where($idColumn, $itemId)
                    ->orderByDesc('created_at')
                    ->get();
            }
        } else {
            $purchases = Purchase::where('store_id', $storeId)
                ->whereHas($relation, function ($q) use ($idColumn, $itemId) {
                    $q->where($idColumn, $itemId);
                })
                ->with([$relation => function ($q) use ($idColumn, $itemId) {
                    $q->where($idColumn, $itemId);
                }])
                ->orderByDesc('created_at')
                ->get();

            if ($purchases->isEmpty()) {
                $purchases = Purchase::whereHas($relation, function ($q) use ($idColumn, $itemId) {
                    $q->where($idColumn, $itemId);
                })
                ->with([$relation => function ($q) use ($idColumn, $itemId) {
                    $q->where($idColumn, $itemId);
                }])
                ->orderByDesc('created_at')
                ->get();
            }
        }

        // 1. حساب آخر تكلفة شراء
        $lastPurchase = $purchases->first();
        $lastItem = ($lastPurchase && $lastPurchase->relationLoaded($relation))
            ? $lastPurchase->{$relation}->first()
            : null;

        $lastQty = $lastItem && $lastItem->count > 0
            ? (float)$lastItem->count
            : (float)($lastPurchase?->quintity ?? 0);

        $lastCost = ($lastPurchase && $lastQty > 0)
            ? ($lastPurchase->total_coast / $lastQty)
            : 0;

        // 2. حساب متوسط تكلفة الكمية بناءً على أحدث فواتير الشراء
        $cost = 0;
        if ($quantity > 0 && $purchases->isNotEmpty()) {
            $remainingStockToValuate = $quantity;
            $totalValueOfStock = 0;

            foreach ($purchases as $purchase) {
                if ($remainingStockToValuate <= 0) break;

                $item = $purchase->relationLoaded($relation) ? $purchase->{$relation}->first() : null;
                $purchaseQty = $item && $item->count > 0
                    ? (float)$item->count
                    : (float)$purchase->quintity;

                if ($purchaseQty <= 0) continue;

                $unitPrice = $purchase->total_coast / $purchaseQty;
                $qtyToTakeFromPurchase = min($remainingStockToValuate, $purchaseQty);
                $totalValueOfStock += ($qtyToTakeFromPurchase * $unitPrice);
                $remainingStockToValuate -= $qtyToTakeFromPurchase;
            }

            $actualValuatedQty = $quantity - $remainingStockToValuate;
            if ($actualValuatedQty > 0) {
                $cost = $totalValueOfStock / $actualValuatedQty;
            }
        }

        return [
            'cost'            => round($cost, 2),
            'unit_cost'       => round($cost, 4), // kept for backwards compatibility
            'last_cost'       => round($lastCost, 2),
            'total_cost'      => round($cost * $quantity, 2),
            'total_last_cost' => round($lastCost * $quantity, 2),
        ];
    }

    /**
     * Transfer multiple products or raw materials between stores in a single request.
     *
     * Expected payload:
     * {
     *   "from_store_id": 1,
     *   "to_store_id": 2,
     *   "transfer_type": "product" | "material",
     *   "products": [ ... ] OR "materials": [ ... ]
     * }
     * Also supports flat single-item payload:
     * {
     *   "from_store_id": 1,
     *   "to_store_id": 2,
     *   "product_id": 5,
     *   "category_id": 2,
     *   "unit_id": 1,
     *   "quintity": 10
     * }
     */
    public function transfer(Request $request){
        // Support single-item payloads (e.g. from standard transfer form) as well as batch arrays
        if (!$request->has('products') && !$request->has('materials')) {
            if ($request->filled('material_id')) {
                $request->merge([
                    'transfer_type' => 'material',
                    'materials' => [[
                        'material_id'          => $request->material_id,
                        'category_material_id' => $request->category_material_id ?? $request->category_id,
                        'unit_id'              => $request->unit_id,
                        'quintity'             => $request->quintity,
                    ]],
                ]);
            } elseif ($request->filled('product_id')) {
                $request->merge([
                    'transfer_type' => 'product',
                    'products' => [[
                        'product_id'  => $request->product_id,
                        'category_id' => $request->category_id,
                        'unit_id'     => $request->unit_id,
                        'quintity'    => $request->quintity,
                    ]],
                ]);
            }
        }

        $transferType = $request->input('transfer_type');
        if (!$transferType) {
            $transferType = !empty($request->materials) ? 'material' : 'product';
        }

        // ── 1. Top-level validation ──────────────────────────────────────────
        $rules = [
            'from_store_id' => ['required', 'exists:purchase_stores,id'],
            'to_store_id'   => ['required', 'exists:purchase_stores,id', 'different:from_store_id'],
            'transfer_type' => ['nullable', 'in:product,material'],
        ];

        if ($transferType === 'material') {
            $items = $request->materials ?? $request->products ?? [];
            $rules['materials'] = ['required_without:products', 'array', 'min:1'];
            $rules['materials.*.quintity']    = ['required', 'numeric', 'min:0.001'];
            $rules['materials.*.unit_id']     = ['required', 'exists:units,id'];
            $rules['materials.*.material_id'] = ['required', 'exists:materials,id'];
        } else {
            $items = $request->products ?? [];
            $rules['products'] = ['required', 'array', 'min:1'];
            $rules['products.*.quintity']    = ['required', 'numeric', 'min:0.001'];
            $rules['products.*.unit_id']     = ['required', 'exists:units,id'];
            $rules['products.*.category_id'] = ['required', 'exists:purchase_categories,id'];
            $rules['products.*.product_id']  = ['required', 'exists:purchase_products,id'];
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fromStoreId = (int)$request->from_store_id;
        $toStoreId   = (int)$request->to_store_id;
        $adminId     = $request->user()->id;

        // ── 2. Stock availability validation (before touching DB) ────────────
        $stockErrors = [];

        if ($transferType === 'material') {
            foreach ($items as $index => $item) {
                $materialId = $item['material_id'];
                $available = $this->material_stock
                    ->where('store_id',    $fromStoreId)
                    ->where('material_id', $materialId)
                    ->value('quantity') ?? 0;

                if ($available < $item['quintity']) {
                    $materialName = $this->materials
                        ->where('id', $materialId)
                        ->value('name') ?? "Material #{$materialId}";

                    $key = isset($request->materials) ? "materials.{$index}.quintity" : "products.{$index}.quintity";
                    $stockErrors[$key] = [
                        "Insufficient stock for \"{$materialName}\": requested {$item['quintity']}, available {$available}."
                    ];
                }
            }
        } else {
            foreach ($items as $index => $item) {
                $productId = $item['product_id'];
                $available = $this->stock
                    ->where('store_id',   $fromStoreId)
                    ->where('product_id', $productId)
                    ->value('quantity') ?? 0;

                if ($available < $item['quintity']) {
                    $productName = $this->products
                        ->where('id', $productId)
                        ->value('name') ?? "Product #{$productId}";

                    $stockErrors["products.{$index}.quintity"] = [
                        "Insufficient stock for \"{$productName}\": requested {$item['quintity']}, available {$available}."
                    ];
                }
            }
        }

        if (!empty($stockErrors)) {
            return response()->json(['errors' => $stockErrors], 422);
        }

        // ── 3. Execute inside a DB transaction ───────────────────────────────
        $createdItems = [];

        DB::transaction(function () use ($items, $transferType, $fromStoreId, $toStoreId, $adminId, &$createdItems) {

            if ($transferType === 'material') {
                foreach ($items as $item) {
                    $quintity   = (float)$item['quintity'];
                    $materialId = (int)$item['material_id'];
                    $catMatId   = $item['category_material_id'] ?? $item['category_id'] ?? null;
                    $unitId     = $item['unit_id'];

                    // Calculate cost metrics exactly like MaterialController::material_stock
                    $costMetrics = $this->calculateItemCost($fromStoreId, 'material_id', $materialId, $quintity);

                    // Record the transfer
                    $newTransfer = $this->purchases->create([
                        'from_store_id'        => $fromStoreId,
                        'to_store_id'          => $toStoreId,
                        'category_material_id' => $catMatId,
                        'material_id'          => $materialId,
                        'admin_id'             => $adminId,
                        'quintity'             => $quintity,
                        'unit_id'              => $unitId,
                        'status'               => 'approve',
                    ]);

                    // Deduct from source store in material_stock
                    $fromStock = $this->material_stock
                        ->where('store_id',    $fromStoreId)
                        ->where('material_id', $materialId)
                        ->first();

                    if ($fromStock) {
                        $fromStock->quantity        -= $quintity;
                        $fromStock->actual_quantity -= $quintity;
                        $fromStock->save();
                    } else {
                        $this->material_stock->create([
                            'category_id'     => $catMatId,
                            'material_id'     => $materialId,
                            'store_id'        => $fromStoreId,
                            'quantity'        => -$quintity,
                            'actual_quantity' => -$quintity,
                            'unit_id'         => $unitId,
                        ]);
                    }

                    // Add to destination store in material_stock
                    $toStock = $this->material_stock
                        ->where('store_id',    $toStoreId)
                        ->where('material_id', $materialId)
                        ->first();

                    if ($toStock) {
                        $toStock->quantity        += $quintity;
                        $toStock->actual_quantity += $quintity;
                        $toStock->save();
                    } else {
                        $this->material_stock->create([
                            'category_id'     => $catMatId,
                            'material_id'     => $materialId,
                            'store_id'        => $toStoreId,
                            'quantity'        => $quintity,
                            'actual_quantity' => $quintity,
                            'unit_id'         => $unitId,
                        ]);
                    }

                    $category = $catMatId ? $this->material_categories->find($catMatId) : null;
                    $material = $this->materials->find($materialId);
                    $unit     = $this->units->find($unitId);

                    $createdItems[] = [
                        'id'              => $newTransfer->id,
                        'type'            => 'material',
                        'category'        => $category?->name ?? '-',
                        'product'         => $material?->name ?? '-',
                        'unit'            => $unit?->name ?? '-',
                        'quintity'        => $quintity,
                        'cost'            => $costMetrics['cost'],
                        'unit_cost'       => $costMetrics['unit_cost'],
                        'last_cost'       => $costMetrics['last_cost'],
                        'total_cost'      => $costMetrics['total_cost'],
                        'total_last_cost' => $costMetrics['total_last_cost'],
                    ];
                }
            } else {
                foreach ($items as $item) {
                    $quintity   = (float)$item['quintity'];
                    $productId  = (int)$item['product_id'];
                    $categoryId = (int)$item['category_id'];
                    $unitId     = $item['unit_id'];

                    // Calculate cost metrics exactly like PurchaseProductController::product_stock
                    $costMetrics = $this->calculateItemCost($fromStoreId, 'product_id', $productId, $quintity);

                    // Record the transfer
                    $newTransfer = $this->purchases->create([
                        'from_store_id' => $fromStoreId,
                        'to_store_id'   => $toStoreId,
                        'category_id'   => $categoryId,
                        'product_id'    => $productId,
                        'admin_id'      => $adminId,
                        'quintity'      => $quintity,
                        'unit_id'       => $unitId,
                        'status'        => 'approve',
                    ]);

                    // Deduct from source store in stock
                    $fromStock = $this->stock
                        ->where('store_id',   $fromStoreId)
                        ->where('product_id', $productId)
                        ->first();

                    if ($fromStock) {
                        $fromStock->quantity        -= $quintity;
                        $fromStock->actual_quantity -= $quintity;
                        $fromStock->save();
                    } else {
                        $this->stock->create([
                            'category_id'     => $categoryId,
                            'product_id'      => $productId,
                            'store_id'        => $fromStoreId,
                            'quantity'        => -$quintity,
                            'actual_quantity' => -$quintity,
                            'unit_id'         => $unitId,
                        ]);
                    }

                    // Add to destination store in stock
                    $toStock = $this->stock
                        ->where('store_id',   $toStoreId)
                        ->where('product_id', $productId)
                        ->first();

                    if ($toStock) {
                        $toStock->quantity        += $quintity;
                        $toStock->actual_quantity += $quintity;
                        $toStock->save();
                    } else {
                        $this->stock->create([
                            'category_id'     => $categoryId,
                            'product_id'      => $productId,
                            'store_id'        => $toStoreId,
                            'quantity'        => $quintity,
                            'actual_quantity' => $quintity,
                            'unit_id'         => $unitId,
                        ]);
                    }

                    $category = $this->categories->find($categoryId);
                    $product  = $this->products->find($productId);
                    $unit     = $this->units->find($unitId);

                    $createdItems[] = [
                        'id'              => $newTransfer->id,
                        'type'            => 'product',
                        'category'        => $category?->name ?? '-',
                        'product'         => $product?->name ?? '-',
                        'unit'            => $unit?->name ?? '-',
                        'quintity'        => $quintity,
                        'cost'            => $costMetrics['cost'],
                        'unit_cost'       => $costMetrics['unit_cost'],
                        'last_cost'       => $costMetrics['last_cost'],
                        'total_cost'      => $costMetrics['total_cost'],
                        'total_last_cost' => $costMetrics['total_last_cost'],
                    ];
                }
            }
        });

        $fromStore = $this->stores->find($fromStoreId);
        $toStore   = $this->stores->find($toStoreId);

        return response()->json([
            'success'         => 'Transfer completed successfully',
            'transfer_type'   => $transferType,
            'count'           => count($items),
            'from_store'      => $fromStore?->name,
            'to_store'        => $toStore?->name,
            'date'            => now()->toDateTimeString(),
            'total_cost'      => round(collect($createdItems)->sum('total_cost'), 2),
            'total_last_cost' => round(collect($createdItems)->sum('total_last_cost'), 2),
            'items'           => $createdItems,
        ]);
    }

    /**
     * Return cost breakdown for a single transfer record (used by the history table PDF button).
     */
    public function transferCost(Request $request, $id)
    {
        $transfer = $this->purchases
            ->with('category', 'product', 'from_store', 'to_store', 'unit', 'material', 'category_material')
            ->findOrFail($id);

        $isMaterial = !empty($transfer->material_id);
        $idColumn   = $isMaterial ? 'material_id' : 'product_id';
        $itemId     = $isMaterial ? $transfer->material_id : $transfer->product_id;
        $quantity   = (float)$transfer->quintity;

        $costMetrics = $this->calculateItemCost((int)$transfer->from_store_id, $idColumn, (int)$itemId, $quantity);

        return response()->json([
            'id'              => $transfer->id,
            'type'            => $isMaterial ? 'material' : 'product',
            'from_store'      => $transfer?->from_store?->name,
            'to_store'        => $transfer?->to_store?->name,
            'category'        => $isMaterial ? ($transfer?->category_material?->name ?? '-') : ($transfer?->category?->name ?? '-'),
            'product'         => $isMaterial ? ($transfer?->material?->name ?? '-') : ($transfer?->product?->name ?? '-'),
            'unit'            => $transfer?->unit?->name,
            'quintity'        => $transfer->quintity,
            'cost'            => $costMetrics['cost'],
            'unit_cost'       => $costMetrics['unit_cost'],
            'last_cost'       => $costMetrics['last_cost'],
            'total_cost'      => $costMetrics['total_cost'],
            'total_last_cost' => $costMetrics['total_last_cost'],
            'status'          => $transfer->status,
            'date'            => $transfer->created_at,
        ]);
    }
}
