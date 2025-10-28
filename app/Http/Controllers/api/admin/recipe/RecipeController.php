<?php

namespace App\Http\Controllers\api\admin\recipe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Recipe;
use App\Models\PurchaseCategory;
use App\Models\Unit;
use App\Models\PurchaseProduct;

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

        return response()->json([
            "recipe" => $recipe,
            "store_categories" => $categories,
            "store_products" => $products,
            "units" => $units,
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
}
