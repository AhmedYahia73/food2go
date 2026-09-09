<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

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
        $purchases = $this->purchases
        ->with('category', 'product', 'from_store', 'to_store', 'admin',
        'material', 'category_material')
        ->get()
        ->map(function($item){
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
            'purchases' => $purchases,
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
     */
    public function transfer(Request $request){
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

        $fromStoreId = $request->from_store_id;
        $toStoreId   = $request->to_store_id;
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
                    $quintity   = $item['quintity'];
                    $materialId = $item['material_id'];
                    $catMatId   = $item['category_material_id'] ?? $item['category_id'] ?? null;
                    $unitId     = $item['unit_id'];

                    // Weighted-average unit cost for material from purchase history
                    $purchaseHistory = DB::table('purchases')
                        ->where('store_id',    $fromStoreId)
                        ->where('material_id', $materialId)
                        ->orderByDesc('created_at')
                        ->get(['total_coast', 'quintity']);

                    $remainingQty = $quintity;
                    $costSum      = 0;
                    $costCount    = 0;
                    foreach ($purchaseHistory as $ph) {
                        if ($remainingQty <= 0) break;
                        $costCount++;
                        $costSum      += $ph->total_coast / ($ph->quintity ?: 1);
                        $remainingQty -= $ph->quintity;
                    }
                    $unitCost  = $costCount > 0 ? $costSum / $costCount : 0;
                    $totalCost = round($unitCost * $quintity, 2);

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
                        'id'         => $newTransfer->id,
                        'type'       => 'material',
                        'category'   => $category?->name ?? '-',
                        'product'    => $material?->name ?? '-',
                        'unit'       => $unit?->name ?? '-',
                        'quintity'   => $quintity,
                        'unit_cost'  => round($unitCost, 4),
                        'total_cost' => $totalCost,
                    ];
                }
            } else {
                foreach ($items as $item) {
                    $quintity   = $item['quintity'];
                    $productId  = $item['product_id'];
                    $categoryId = $item['category_id'];
                    $unitId     = $item['unit_id'];

                    // Weighted-average unit cost for product from purchase history
                    $purchaseHistory = DB::table('purchases')
                        ->where('store_id',   $fromStoreId)
                        ->where('product_id', $productId)
                        ->orderByDesc('created_at')
                        ->get(['total_coast', 'quintity']);

                    $remainingQty = $quintity;
                    $costSum      = 0;
                    $costCount    = 0;
                    foreach ($purchaseHistory as $ph) {
                        if ($remainingQty <= 0) break;
                        $costCount++;
                        $costSum      += $ph->total_coast / ($ph->quintity ?: 1);
                        $remainingQty -= $ph->quintity;
                    }
                    $unitCost  = $costCount > 0 ? $costSum / $costCount : 0;
                    $totalCost = round($unitCost * $quintity, 2);

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
                        'id'         => $newTransfer->id,
                        'type'       => 'product',
                        'category'   => $category?->name ?? '-',
                        'product'    => $product?->name ?? '-',
                        'unit'       => $unit?->name ?? '-',
                        'quintity'   => $quintity,
                        'unit_cost'  => round($unitCost, 4),
                        'total_cost' => $totalCost,
                    ];
                }
            }
        });

        $fromStore = $this->stores->find($fromStoreId);
        $toStore   = $this->stores->find($toStoreId);

        return response()->json([
            'success'       => 'Transfer completed successfully',
            'transfer_type' => $transferType,
            'count'         => count($items),
            'from_store'    => $fromStore?->name,
            'to_store'      => $toStore?->name,
            'date'          => now()->toDateTimeString(),
            'items'         => $createdItems,
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

        // Weighted average unit cost from purchase history in the source store
        $purchaseHistory = DB::table('purchases')
            ->where('store_id', $transfer->from_store_id)
            ->where($idColumn,  $itemId)
            ->orderByDesc('created_at')
            ->get(['total_coast', 'quintity']);

        $remainingQty = $transfer->quintity;
        $costSum      = 0;
        $costCount    = 0;
        foreach ($purchaseHistory as $ph) {
            if ($remainingQty <= 0) break;
            $costCount++;
            $costSum      += $ph->total_coast / ($ph->quintity ?: 1);
            $remainingQty -= $ph->quintity;
        }
        $unitCost  = $costCount > 0 ? $costSum / $costCount : 0;
        $totalCost = round($unitCost * $transfer->quintity, 2);

        return response()->json([
            'id'         => $transfer->id,
            'type'       => $isMaterial ? 'material' : 'product',
            'from_store' => $transfer?->from_store?->name,
            'to_store'   => $transfer?->to_store?->name,
            'category'   => $isMaterial ? ($transfer?->category_material?->name ?? '-') : ($transfer?->category?->name ?? '-'),
            'product'    => $isMaterial ? ($transfer?->material?->name ?? '-') : ($transfer?->product?->name ?? '-'),
            'unit'       => $transfer?->unit?->name,
            'quintity'   => $transfer->quintity,
            'unit_cost'  => round($unitCost, 4),
            'total_cost' => $totalCost,
            'status'     => $transfer->status,
            'date'       => $transfer->created_at,
        ]);
    }
}
