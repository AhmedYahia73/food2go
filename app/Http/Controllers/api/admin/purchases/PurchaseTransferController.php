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
     * Transfer multiple products between stores in a single request.
     *
     * Expected payload:
     * {
     *   "from_store_id": 1,
     *   "to_store_id": 2,
     *   "products": [
     *     { "quintity": 22, "unit_id": 1, "category_id": 1, "product_id": 1 },
     *     { "quintity": 5,  "unit_id": 2, "category_id": 1, "product_id": 3 }
     *   ]
     * }
     */
    public function transfer(Request $request){

        // ── 1. Top-level validation ──────────────────────────────────────────
        $validator = Validator::make($request->all(), [
            'from_store_id'          => ['required', 'exists:purchase_stores,id'],
            'to_store_id'            => ['required', 'exists:purchase_stores,id', 'different:from_store_id'],
            'products'               => ['required', 'array', 'min:1'],
            'products.*.quintity'    => ['required', 'numeric', 'min:0.001'],
            'products.*.unit_id'     => ['required', 'exists:units,id'],
            'products.*.category_id' => ['required', 'exists:purchase_categories,id'],
            'products.*.product_id'  => ['required', 'exists:purchase_products,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $fromStoreId = $request->from_store_id;
        $toStoreId   = $request->to_store_id;
        $products    = $request->products;
        $adminId     = $request->user()->id;

        // ── 2. Stock availability validation (before touching DB) ────────────
        $stockErrors = [];

        foreach ($products as $index => $item) {
            $available = $this->stock
                ->where('store_id',   $fromStoreId)
                ->where('product_id', $item['product_id'])
                ->value('quantity') ?? 0;

            if ($available < $item['quintity']) {
                // Get product name for a friendly error message
                $productName = $this->products
                    ->where('id', $item['product_id'])
                    ->value('name') ?? "Product #{$item['product_id']}";

                $stockErrors["products.{$index}.quintity"] = [
                    "Insufficient stock for \"{$productName}\": "
                    . "requested {$item['quintity']}, available {$available}."
                ];
            }
        }

        if (!empty($stockErrors)) {
            return response()->json(['errors' => $stockErrors], 422);
        }

        // ── 3. Execute inside a DB transaction ───────────────────────────────
        DB::transaction(function () use ($products, $fromStoreId, $toStoreId, $adminId) {

            foreach ($products as $item) {
                $quintity   = $item['quintity'];
                $productId  = $item['product_id'];
                $categoryId = $item['category_id'];
                $unitId     = $item['unit_id'];

                // 3a. Record the transfer
                $this->purchases->create([
                    'from_store_id' => $fromStoreId,
                    'to_store_id'   => $toStoreId,
                    'category_id'   => $categoryId,
                    'product_id'    => $productId,
                    'admin_id'      => $adminId,
                    'quintity'      => $quintity,
                    'unit_id'       => $unitId,
                    'status'        => 'approve',
                ]);

                // 3b. Deduct from source store
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

                // 3c. Add to destination store
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
            }
        });

        return response()->json([
            'success' => 'Transfer completed successfully',
            'count'   => count($products),
        ]);
    }
}
