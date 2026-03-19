<?php

namespace App\Http\Controllers\api\admin\material;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\MaterialCategory;
use App\Models\MaterialStock;
use App\Models\Material;
use App\Models\Purchase;
use App\Models\PurchaseStore;
use App\Models\Unit;

class MaterialController extends Controller
{
    public function __construct(private Material $product,
    private MaterialCategory $categories, private MaterialStock $stock){}

    public function view(Request $request){
        $product = $this->product
        ->with("category", "start_stock.store")
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'status' => $item->status,
                'category_id' => $item->category_id,
                'category' => $item?->category?->name,
                'min_stock' => $item->min_stock,
                'start_stock' => $item->start_stock
                ->map(function($element){
                    return [
                        "id" => $element->id,
                        "start_stock" => $element->start_stock,
                        "cost" => $element->cost,
                        "unit" => $element->unit?->name,
                        "store" => $element->store?->name,
                    ];
                }),
            ];
        }); 
        $categories = $this->categories
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get();

        return response()->json([
            'materials' => $product,
            'categories' => $categories,
        ]);
    }

    public function lists(Request $request){
        $stores = PurchaseStore::
        select("id", "name")
        ->get();
        $units = Unit::
        select("id", "name")
        ->where("status", 1)
        ->get();

        return response()->json([
            "stores" => $stores,
            "units" => $units,
        ]);
    }

    public function material_stock(Request $request){
        $validator = Validator::make($request->all(), [
            'store_id' => ['required', 'exists:purchase_stores,id'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $purchases = Purchase::where("store_id", $request->store_id)
        ->orderByDesc('id')
        ->get()
        ->groupBy('product_id');
        $stocks = $this->stock
        ->where("store_id", $request->store_id)
        ->get();
        $product = $this->product
        ->with("start_stock")
        ->get()
        ->map(function($item) use($stocks, $purchases, $request){
            $stock = $item?->stock_items
            ?->where("store_id", $request->store_id)?->first()?->quantity ?? 0;
            $purchase = $purchases[$item->id] ?? collect();
            $quantity_stock = $stock;
            $cost = $item->start_stock
            ->where("store_id", $request->store_id)
            ->first()?->cost ?? 0;
            $count = $item->start_stock
            ->where("store_id", $request->store_id)
            ->first()?->start_stock ?? 0;
            $stock += $count;
            $last_cost = $cost; 
            foreach ($purchase as $element) {
                if($quantity_stock > 0){
                    $count++;
                    if($element->quintity > 0){
                        $cost += $element->total_coast / $element->quintity;
                    }
                }
                else{
                    break;
                }
                $quantity_stock -= $element->quintity;
            }
            $last_cost = isset($purchase[0]) ? $purchase[0]->total_coast / $purchase[0]->quintity : 0;
            $cost /= ($count == 0 ? 1 : $count);
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'status' => $item->status,
                'category_id' => $item->category_id,
                'category' => $item?->category?->name,
                'min_stock' => $item->min_stock,
                "stock" => $stock,
                "cost" => $cost, 
                "last_cost" => $last_cost,
                "total_cost" => $cost * $stock,
                "total_last_cost" => $last_cost * $stock,
            ];
        }); 
        $categories = $this->categories
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get();

        return response()->json([
            'materials' => $product,
            'categories' => $categories,
            "total_cost" => collect($product)->sum("total_cost"),
            "total_last_cost" => collect($product)->sum("total_last_cost"),
        ]);
    }
    
    public function product(Request $request, $id){ 
        $product = $this->product
        ->where('id', $id)
        ->with('category:id,name', "start_stock.store")
        ->first();

        return response()->json([
            'material' => $product,
        ]);
    }

    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'boolean'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->product
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status' => $request->status ? 'active' : 'banned'
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'description' => ['sometimes'],
            'status' => ['required', 'boolean'],
            'category_id' => ['required', 'exists:material_categories,id'],
            'min_stock' => ['sometimes', 'numeric'],
            'matrial_store' => ["array"],
            'matrial_store.*.start_stock' => ["required", "numeric"],
            'matrial_store.*.cost' => ["required", "numeric"],
            'matrial_store.*.unit_id' => ["required", "exists:units,id"], 
            'matrial_store.*.store_id' => ["required", "exists:units,id"], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $productRequest = $validator->validated();
        $product = $this->product
        ->create($productRequest);
        $matrial_store = $request->matrial_store ?? [];
        foreach ($matrial_store as $item) {
            $product->start_stock()
            ->create([
                "start_stock" => $item['start_stock'],
                "cost" => $item['cost'],
                "unit_id" => $item['unit_id'],
                "store_id" => $item['store_id'],
            ]);
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'description' => ['sometimes'],
            'status' => ['required', 'boolean'],
            'category_id' => ['required', 'exists:material_categories,id'],
            'min_stock' => ['sometimes', 'numeric'],
            'matrial_store' => ["array"],
            'matrial_store.*.start_stock' => ["required", "numeric"],
            'matrial_store.*.cost' => ["required", "numeric"],
            'matrial_store.*.unit_id' => ["required", "exists:units,id"], 
            'matrial_store.*.store_id' => ["required", "exists:units,id"],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $productRequest = $validator->validated();
        $product = $this->product
        ->where('id', $id)
        ->first();
        $product->update($productRequest);
        $matrial_store = $request->matrial_store ?? [];
        $product->start_stock()->delete();
        foreach ($matrial_store as $item) {
            $product->start_stock()
            ->create([
                "start_stock" => $item['start_stock'],
                "cost" => $item['cost'],
                "unit_id" => $item['unit_id'],
                "store_id" => $item['store_id'],
            ]);
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete(Request $request, $id){
        $this->product
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
