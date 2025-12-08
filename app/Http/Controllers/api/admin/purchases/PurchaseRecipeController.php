<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseRecipe;
use App\Models\Unit;
use App\Models\MaterialCategory;
use App\Models\Material;

class PurchaseRecipeController extends Controller
{
    public function __construct(private PurchaseRecipe $recipe, private MaterialCategory $category,
    private Material $product, private Unit $units){}

    public function view(Request $request, $id){
        $recipe = $this->recipe
        ->where("product_id", $id)
        ->with(["product:id,name", "material:id,name", 
        "material_category:id,name", "unit:id,name"])
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
                "material" => $item->material,
                "material_category" => $item->material_category,
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
            "material_categories" => $categories,
            "material_products" => $products,
            "units" => $units,
        ]);
    }

    public function purchase_item(Request $request, $id){
        $recipe = $this->recipe
        ->where("id", $id)
        ->with(["product:id,name", "material:id,name", 
        "material_category:id,name", "unit:id,name"])
        ->first();  

        return response()->json([         
            "id" => $item->id,
            "weight" => $item->weight,
            "status" => $item->status,
            "product" => $item->product ? [
                "id" => $item->product->id,
                "name" => $item->product->name,
            ]: null, 
            "material" => $item->material,
            "material_category" => $item->material_category,
            "unit" => $item->unit,
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

        $recipe = $this->recipe
         ->where("id", $id)
         ->first();
         if(!$recipe){
            return response()->json([
                "errors" => "Recipe is not found"
            ], 400);
         }
        $recipe->update([
            "status" => $request->status ?? $recipe->status,
        ]);

        return response()->json([
            "success" => "You update status success"
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => ["required", "exists:purchase_products,id"],
            'material_product_id' => ["required", "exists:materials,id"],
            'material_category_id' => ["required", "exists:material_categories,id"],
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
            'material_product_id' => ["required", "exists:materials,id"],
            'material_category_id' => ["required", "exists:material_categories,id"],
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
            "material_category_id" => $request->material_category_id ?? $recipe->material_category_id,
            "material_product_id" => $request->material_product_id ?? $recipe->material_product_id,
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
