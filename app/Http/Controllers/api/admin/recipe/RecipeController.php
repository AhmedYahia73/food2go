<?php

namespace App\Http\Controllers\api\admin\recipe;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Recipe;

class RecipeController extends Controller
{
    public function __construct(private Recipe $recipe){}

    public function view(Request $request, $id){
        $recipe = $this->recipe
        ->where("product_id", $id)
        ->get();

        return response()->json([
            "recipe" => $recipe
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'exists:products,id'],
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
            'unit_id' => ['sometimes', 'exists:units,id'],
            'weight' => ['sometimes', 'numeric'],
            'status' => ['sometimes', 'boolean'],
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
