<?php

namespace App\Http\Controllers\api\admin\recipe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
 
use App\Models\PurchaseCategory;
use App\Models\Unit;
use App\Models\PurchaseProduct;

use App\Models\VariationRecipe;
use App\Models\VariationProduct;

class VariationRecipeController extends Controller
{ 
    public function __construct(private PurchaseCategory $category,
    private PurchaseProduct $product, private Unit $units){}

    public function view_variations($id){
        $variations = VariationProduct::
        where("product_id", $id)
        ->with("options")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "options" => $item->options
                ->map(function($element){
                    return [
                        "id" => $element->id,
                        "name" => $element->name,
                    ];
                }),
            ];
        });

        return response()->json([
            "variations" => $variations
        ]);
    }

    public function lists(Request $request){ 
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
            "store_categories" => $categories,
            "store_products" => $products,
            "units" => $units,
        ]);
    }

    public function view_recipes($id){
        $variations = VariationRecipe::
        where("option_id", $id)
        ->with(["option", "store_category:id,name", 
        "store_product:id,name", "unit:id,name"])
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "weight" => $item->weight,
                "status" => $item->status, 
                "variation" => $item?->variation?->name,
                "option" => $item?->option?->name,
                "store_category" => $item->store_category,
                "store_product" => $item->store_product,
                "unit" => $item->unit,
            ];
        });

        return response()->json([
            "variations" => $variations
        ]);
    }

    public function variation_recipe_item(Request $request, $id){
        $variation = VariationRecipe::
        where("id", $id)
        ->with(["options", "store_category:id,name", 
        "store_product:id,name", "unit:id,name"])
        ->first();

        return response()->json([
            "id" => $variation->id,
            "weight" => $variation->weight,
            "option_id" => $variation->option_id,
            "variation_id" => $variation->variation_id,
            "unit_id" => $variation->unit_id,
            "store_category_id" => $variation->store_category_id,
            "store_product_id" => $variation->store_product_id,
            "status" => $variation->status,
            "variation" => $variation?->variation?->name,
            "option" => $variation?->option?->name,
            "store_category" => $variation->store_category,
            "store_product" => $variation->store_product,
            "unit" => $variation->unit,
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
    }


    public function create(Request $request){ 
        $validator = Validator::make($request->all(), [
            'variation_id' => ['required', 'exists:variation_products,id'],
            'option_id' => ['required', 'exists:option_products,id'],
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

        $variation = VariationRecipe::create([
            'option_id' => $request->option_id,
            'variation_id' => $request->variation_id,
            'unit_id' => $request->unit_id,
            'weight' => $request->weight,
            'store_category_id' => $request->store_category_id,
            'store_product_id' => $request->store_product_id,
            'status' => $request->status,
        ]);

        return response()->json([
            "success" => "You add recipe success",
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'variation_id' => ['required', 'exists:variation_products,id'],
            'option_id' => ['required', 'exists:option_products,id'],
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

        $variation = VariationRecipe::
        where("id", $id)
        ->first();
        if(empty($variation)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }

        $variation->update([
            'option_id' => $request->option_id,
            'variation_id' => $request->variation_id,
            'unit_id' => $request->unit_id,
            'weight' => $request->weight,
            'store_category_id' => $request->store_category_id,
            'store_product_id' => $request->store_product_id,
            'status' => $request->status,
        ]);

        return response()->json([
            "success" => "You update recipe success",
        ]);
    }

    public function delete(Request $request, $id){
        
        $variation = VariationRecipe::
        where("id", $id)
        ->first();
        if(empty($variation)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        
        $variation->delete();

        return response()->json([
            "success" => "You delete recipe success",
        ]);
    }
}
