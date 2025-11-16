<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseRecipe;
use App\Models\PurchaseProduct;
use App\Models\PurchaseCategory;

use App\Models\Manufaturing;
use App\Models\ManufaturingRecipe;
use App\Models\MaterialStock;
use App\Models\PurchaseStore;
use App\Models\Material;

class ManufacturingController extends Controller
{
    public function __construct(private PurchaseRecipe $recipe,
    private PurchaseProduct $products, private PurchaseCategory $category,
    private Manufaturing $maufaturing, private ManufaturingRecipe $maufaturing_recipe, 
    private MaterialStock $stock, private Material $materials,
    private PurchaseStore $stores){}

    public function lists(Request $request){
        $products = $this->products
        ->select("id", "name", "category_id") 
        ->where("status", 1)
        ->get();
        $categories = $this->category
        ->select("id", "name")
        ->where("status", 1)
        ->get();
        $stores = $this->stores
        ->select("id", "name")
        ->get();

        return response()->json([
            "products" => $products,
            "categories" => $categories,
            "stores" => $stores,
        ]);
    }

    public function product_recipe(Request $request){
        $validator = Validator::make($request->all(), [
            'store_id' => ["required", "exists:purchase_stores,id"],
            'product_id' => ["required", "exists:purchase_products,id"],
            'quantity' => ["required", "numeric"],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
 
        $recipes = $this->recipe 
        ->with(["material_category:id,name", "material:id,name",
        "unit:id,name"])
        ->where("product_id", $request->product_id)
        ->whereHas('material', function($query){
            $query->where('status', 1);
        })
        ->get()
        ->map(function($item) use($request){
            $available_quantity = \App\Models\MaterialStock::query()
            ->where('store_id', $request->store_id)
            ->where('material_id', $item->material_product_id)
            ->first();
            return [
                "id" => $item->id,
                "weight" => $item->weight * $request->quantity,
                "material_category" => $item->material_category,
                "material" => $item->material,
                "unit" => $item->unit,
                "available_quantity" => $available_quantity->quantity ?? 0,
                'available' => ($available_quantity->quantity ?? 0) >= ($item->weight * $request->quantity),
            ];
        });

        return response()->json([
            "recipes" => $recipes
        ]);
    }
// food2go290@gmail.com
// Food2go@2020
    public function manufacturing(Request $request){
        $validator = Validator::make($request->all(), [
            'materials' => ['required', 'array'],
            'materials.*.id' => ['required', 'exists:materials,id'],
            'materials.*.weight' => ['required', 'numeric'],
            'store_id' => ["required", "exists:purchase_stores,id"],
            'product_id' => ["required", "exists:purchase_products,id"],
            'quantity' => ['required', 'numeric'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        foreach ($request->materials as $item) {
            $stock = $this->stock
            ->where('store_id', $request->store_id)
            ->where("material_id", $item['id'])
            ->where("quantity", '>=', $item['weight'])
            ->first();
            if(empty($stock)){
                $material = $this->materials
                ->where('id', $item['id'])
                ->first();
                return response()->json([
                    'errors' => $material->name . ' is not in stock'
                ], 400);
            }
        }
        // manufactring history
        $maufaturing = $this->maufaturing
        ->create([
            'product_id' => $request->product_id,
            'store_id' => $request->store_id,
            'quantity' => $request->quantity,
        ]);
        foreach ($request->materials as $item) {
            $this->maufaturing_recipe
            ->create([
                'manufaturing_id' => $maufaturing->id,
                'material_id' => $item['id'],
                'quantity' => $item['weight'],
            ]);
            $stock = $this->stock
            ->where('store_id', $request->store_id)
            ->where("material_id", $item['id'])
            ->first();
            $stock->quantity -= $item['weight'];
            $stock->save();
        } 

        return response()->json([
            'success' => 'You moke Product success'
        ]);
    }

    public function manufacturing_history(Request $request){
        $maufaturing = $this->maufaturing
        ->with(['product:id,name', 'store:id,name'])
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'product' => $item?->product?->name,
                'store' => $item?->store?->name,
                'quantity' => $item->quantity,
                'date' => $item->created_at,
            ];
        });

        return response()->json([
            'maufaturing' => $maufaturing
        ]);
    }

    public function manufacturing_recipe(Request $request, $id){
        $maufaturing_recipe = $this->maufaturing_recipe
        ->where('manufaturing_id', $id)
        ->with(['material:id,name'])
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'material' => $item?->material?->name,
                'quantity' => $item->quantity, 
            ];
        });

        return response()->json([
            'maufaturing_recipe' => $maufaturing_recipe
        ]);
    }
}
