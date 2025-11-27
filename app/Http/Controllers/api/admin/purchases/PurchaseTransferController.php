<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
                    ]);
                }
                else{
                    $stock->quantity -= $purchases->quintity;
                    $stock->save();
                }

                if(empty($to_store)){
                    $this->stock
                    ->create([
                        'category_id' => $purchases->category_id,
                        'product_id' => $purchases->product_id,
                        'store_id' => $purchases->to_store_id,
                        'quantity' => $purchases->quintity,
                    ]);
                }
                else{
                    $stock = $this->stock
                    ->where('category_id', $purchases->category_id)
                    ->where('product_id', $purchases->product_id)
                    ->where('store_id', $purchases->to_store_id)
                    ->first();
                    $stock->quantity += $purchases->quintity;
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
                        'unit_id' => -$product->unit_id,
                    ]);
                }
                else{
                    $material_stock = $this->material_stock 
                    ->where('material_id', $purchases->material_id)
                    ->where('store_id', $purchases->from_store_id)
                    ->first();
                    $material_stock->quantity -= $purchases->quintity;
                    $material_stock->save();
                }

                if(empty($to_store)){
                    $this->material_stock
                    ->create([
                        'category_id' => $purchases->category_id,
                        'material_id' => $purchases->material_id,
                        'store_id' => $purchases->to_store_id,
                        'quantity' => $purchases->quintity,
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
                    $material_stock->save();
                } 
            }
        }

        return response()->json([
            'success' => 'You update status success'
        ]);
    }

    public function transfer(Request $request){
        $validator = Validator::make($request->all(), [
            'from_store_id' => ['required', 'exists:purchase_stores,id'], 
            'to_store_id' => ['required', 'exists:purchase_stores,id'], 
            'category_id' => ['exists:purchase_categories,id'], 
            'product_id' => ['exists:purchase_products,id'], 
            
            'material_id' => ['exists:materials,id'],
            'category_material_id' => ['exists:material_categories,id'],

            'quintity' => ['required', 'numeric'],
            'unit_id' => ['required', 'exists:units,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        if(empty($request->material_id) && empty($request->product_id)){
            return response()->json([
                "errors" => "You must enter material_id or product_id"
            ], 400);
        }

        $this->purchases
        ->create([
            'from_store_id' => $request->from_store_id,
            'to_store_id' => $request->to_store_id,
            'category_id' => $request->category_id ?? null,
            'product_id' => $request->product_id ?? null,
            'material_id' => $request->material_id ?? null,
            'category_material_id' => $request->category_material_id ?? null,
            'admin_id' => $request->user()->id,
            'quintity' => $request->quintity,
            'unit_id' => $request->unit_id,
            'status' => 'approve',
        ]);
        // stock
        if(!empty($request->product_id)){ 
            $from_store = $this->stock
            ->where('store_id', $request->from_store_id)
            ->where('product_id', $request->product_id)
            ->first();
            $to_store = $this->stock
            ->where('store_id', $request->to_store_id)
            ->where('product_id', $request->product_id)
            ->first();
            if(empty($from_store)){
                $product = $this->products
                ->where("id", $request->product_id)
                ->first();
                $this->stock
                ->create([
                    'category_id' => $request->category_id,
                    'product_id' => $request->product_id,
                    'store_id' => $request->from_store_id,
                    'quantity' => -$request->quintity,
                    'unit_id' => -$product->unit_id,
                ]);
            }
            else{
                $stock = $this->stock
                ->where('category_id', $request->category_id)
                ->where('product_id', $request->product_id)
                ->where('store_id', $request->from_store_id)
                ->first();
                $stock->quantity -= $request->quintity;
                $stock->save();
            }

            if(empty($to_store)){
                $this->stock
                ->create([
                    'category_id' => $request->category_id,
                    'product_id' => $request->product_id,
                    'store_id' => $request->to_store_id,
                    'quantity' => $request->quintity,
                    'unit_id' => $request->unit_id,
                ]);
            }
            else{
                $stock = $this->stock
                ->where('category_id', $request->category_id)
                ->where('product_id', $request->product_id)
                ->where('store_id', $request->to_store_id)
                ->first();
                $stock->quantity += $request->quintity;
                $stock->save();
            }
        }
        else{ 
            $from_store = $this->material_stock
            ->where('store_id', $request->from_store_id)
            ->where('material_id', $request->material_id)
            ->first();
            $to_store = $this->material_stock
            ->where('store_id', $request->to_store_id)
            ->where('material_id', $request->material_id)
            ->first();
            if(empty($from_store)){
                $product = $this->products
                ->where("id", $request->material_id)
                ->first();
                $this->material_stock
                ->create([
                    'category_id' => $request->category_id,
                    'material_id' => $request->material_id,
                    'store_id' => $request->from_store_id,
                    'quantity' => -$request->quintity,
                    'unit_id' => -$product->unit_id,
                ]);
            }
            else{
                $material_stock = $this->material_stock
                ->where('material_id', $request->material_id)
                ->where('store_id', $request->from_store_id)
                ->first();
                $material_stock->quantity -= $request->quintity;
                $material_stock->save();
            }

            if(empty($to_store)){
                $this->material_stock
                ->create([
                    'category_id' => $request->category_id,
                    'material_id' => $request->material_id,
                    'store_id' => $request->to_store_id,
                    'quantity' => $request->quintity,
                    'unit_id' => $request->unit_id,
                ]);
            }
            else{
                $material_stock = $this->material_stock
                ->where('material_id', $request->material_id)
                ->where('store_id', $request->to_store_id)
                ->first();
                $material_stock->quantity += $request->quintity;
                $material_stock->save();
            } 
        }

        return response()->json([
            'success' => 'You update status success'
        ]);
    }
}
