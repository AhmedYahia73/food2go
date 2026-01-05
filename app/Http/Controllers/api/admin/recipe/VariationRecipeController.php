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
        ->select("id", "name", "category_id", "sub_category_id")
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
                "variation_id" => $item->variation_id,
                "option_id" => $item->option_id,
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
            "recipes" => $variations
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

        $variation = VariationRecipe::
        where("id", $id) 
        ->update([
            "status" => $request->status
        ]);

        return response()->json([
            "success" => "You update status success",
        ]);
    }


    public function create(Request $request){ 
        $validator = Validator::make($request->all(), [
            "variations" => ["required", "array"],
            'variations.*.id' => ['required', 'exists:variation_products,id'],
            'variations.*.options' => ['required', 'array'],
            'variations.*.options.*.id' => ['required', 'exists:option_products,id'],
            'variations.*.options.*.store_product_id' => ["required", "exists:purchase_products,id"],
            'variations.*.options.*.store_category_id' => ["required", "exists:purchase_categories,id"],
            'variations.*.options.*.unit_id' => ['required', 'exists:units,id'],
            'variations.*.options.*.weight' => ['required', 'numeric'],
            'variations.*.options.*.status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        foreach ($request->variations as $variation_item) {
            foreach ($variation_item['options'] as $option_item) {
                $variation = VariationRecipe::create([
                    'option_id' => $option_item['id'],
                    'variation_id' => $variation_item['id'],
                    'unit_id' => $option_item['unit_id'],
                    'weight' => $option_item['weight'],
                    'store_category_id' => $option_item['store_category_id'],
                    'store_product_id' => $option_item['store_product_id'],
                    'status' => $option_item['status'],
                ]);
            }
        }

        return response()->json([
            "success" => "You add recipe success",
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            "variations" => ["required", "array"],
            'variations.*.id' => ['required', 'exists:variation_products,id'],
            'variations.*.options' => ['required', 'array'],
            'variations.*.options.*.id' => ['required', 'exists:option_products,id'],
            'variations.*.options.*.store_product_id' => ["required", "exists:purchase_products,id"],
            'variations.*.options.*.store_category_id' => ["required", "exists:purchase_categories,id"],
            'variations.*.options.*.unit_id' => ['required', 'exists:units,id'],
            'variations.*.options.*.weight' => ['required', 'numeric'],
            'variations.*.options.*.status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
 
        $variation = VariationRecipe::
        where("variation_id", $id)
        ->delete();  

        foreach ($request->variations as $variation_item) {
            foreach ($variation_item['options'] as $option_item) {
                $variation = VariationRecipe::create([
                    'option_id' => $option_item['id'],
                    'variation_id' => $variation_item['id'],
                    'unit_id' => $option_item['unit_id'],
                    'weight' => $option_item['weight'],
                    'store_category_id' => $option_item['store_category_id'],
                    'store_product_id' => $option_item['store_product_id'],
                    'status' => $option_item['status'],
                ]);
            }
        } 

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
