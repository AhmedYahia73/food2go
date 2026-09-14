<?php

namespace App\Http\Controllers\api\admin\recipe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\Recipe;
use App\Models\PurchaseCategory;
use App\Models\Unit;
use App\Models\PurchaseProduct;
use App\Models\PurchaseStore;

class RecipeController extends Controller
{
    public function __construct(private Recipe $recipe, private PurchaseCategory $category,
    private PurchaseProduct $product, private Unit $units){}

    public function view(Request $request, $id){
        $recipe = $this->recipe
        ->where("product_id", $id)
        ->with(["product:id,name", "store_category:id,name", 
        "store_product:id,name", "unit:id,name"])
        ->get()
        ->map(function($item){
            return [         
                "id" => $item->id,
                "weight" => $item->weight,
                "status" => $item->status,
                "product" => $item->product ? [
                    "id" => $item->product->id,
                    "name" => $item->product->name,
                ]: null, 
                "store_category" => $item->store_category,
                "store_product" => $item->store_product,
                "unit" => $item->unit,
            ];
        });
        $categories = $this->category
        ->select("id", "name")
        ->where("status", 1)
        ->get();
        $products = $this->product
        ->select("id", "name")
        ->where("status", 1)
        ->get();
        $units = $this->units
        ->select("id", "name")
        ->where("status", 1)
        ->get();
        $stores = PurchaseStore::select("id", "name")
        ->where("status", 1)
        ->get();

        return response()->json([
            "recipe" => $recipe,
            "store_categories" => $categories,
            "store_products" => $products,
            "units" => $units,
            "stores" => $stores,
        ]);
    }

    public function recipe_item(Request $request, $id){
        $recipe = $this->recipe
        ->where("id", $id)
        ->with(["product:id,name", "store_category:id,name", 
        "store_product:id,name", "unit:id,name"])
        ->first();
        if(empty($recipe)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400); 
        }

        return response()->json( [         
            "id" => $recipe->id,
            "weight" => $recipe->weight,
            "status" => $recipe->status,
            "product" => $recipe->product ? [
                "id" => $recipe->product->id,
                "name" => $recipe->product->name,
            ]: null, 
            "store_category" => $recipe->store_category,
            "store_product" => $recipe->store_product,
            "unit" => $recipe->unit,
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'exists:products,id'],
            'store_product_id' => ["required", "exists:purchase_products,id"],
            'store_category_id' => ["required", "exists:purchase_categories,id"],
            'unit_id' => ['required', 'exists:units,id'],
            'weight' => ['required', 'numeric'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $recipeRequest = $validator->validated();
        $this->recipe
        ->create($recipeRequest);

        return response()->json([
            "success" => "You add recipe success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'store_product_id' => ["required", "exists:purchase_products,id"],
            'store_category_id' => ["required", "exists:purchase_categories,id"],
            'unit_id' => ['required', 'exists:units,id'],
            'weight' => ['required', 'numeric'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $recipe = $this->recipe
         ->where("id", $id)
         ->first();
         if(!$recipe){
            return response()->json([
                "errors" => "Recipe is not found"
            ], 400);
         }
        $recipe->update([
            "unit_id" => $request->unit_id ?? $recipe->unit_id,
            "weight" => $request->weight ?? $recipe->weight,
            "store_product_id" => $request->store_product_id ?? $recipe->store_product_id,
            "store_category_id" => $request->store_category_id ?? $recipe->store_category_id,
            "status" => $request->status ?? $recipe->status,
        ]);

        return response()->json([
            "success" => "You update recipe success"
        ]);
    }

    public function delete(Request $request, $id){
        $recipe = $this->recipe
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete recipe success"
        ]);
    }

    public function addProductRecipe(Request $request, $id = null){
        $productId = $id ?? $request->product_id;

        $validator = Validator::make(array_merge($request->all(), ['product_id' => $productId]), [
            'product_id' => ['required', 'exists:products,id'],
            'name' => ['required'],
            'description' => ['sometimes', 'nullable'],
            'status' => ['required', 'boolean'],
            'category_id' => ['required', 'exists:purchase_categories,id'],
            'min_stock' => ['sometimes', 'nullable', 'numeric'],
            'unit_id' => ['required', 'exists:units,id'],
            'weight' => ['required', 'numeric'],
            'product_store' => ['sometimes', 'array'],
            'product_store.*.start_stock' => ['required', 'numeric'],
            'product_store.*.cost' => ['required', 'numeric'],
            'product_store.*.unit_id' => ['required', 'exists:units,id'],
            'product_store.*.store_id' => ['required', 'exists:purchase_stores,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Create Purchase Product
            $product = $this->product->create([
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
                'category_id' => $request->category_id,
                'min_stock' => $request->min_stock ?? 0,
            ]);

            // 2. Create Start Stocks for each store
            $product_store = $request->product_store ?? [];
            foreach ($product_store as $item) {
                $product->start_stock()->create([
                    "start_stock" => $item['start_stock'],
                    "cost" => $item['cost'],
                    "unit_id" => $item['unit_id'],
                    "store_id" => $item['store_id'],
                ]);
            }

            // 3. Create Recipe linking menu product with new store product
            $recipe = $this->recipe->create([
                "product_id" => $productId,
                "store_product_id" => $product->id,
                "store_category_id" => $product->category_id,
                "unit_id" => $request->unit_id,
                "weight" => $request->weight,
                "status" => 1,
            ]);

            DB::commit();

            return response()->json([
                'success' => 'Product and recipe added successfully',
                'product' => $product,
                'recipe' => $recipe,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'errors' => 'Failed to create product recipe: ' . $e->getMessage(),
            ], 500);
        }
    }
}
