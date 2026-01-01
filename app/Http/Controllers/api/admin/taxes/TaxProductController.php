<?php

namespace App\Http\Controllers\api\admin\taxes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Tax;
use App\Models\Product;
use App\Models\Category;

class TaxProductController extends Controller
{
    public function view(Request $request, $id){
        $products = Product::
        where("tax_id", $id)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "category_id" => $item->category_id,
                "sub_category_id" => $item->sub_category_id,
            ];
        });

        return response()->json([
            "products" => $products
        ]);
    }

    public function lists(Request $request){
        $categories = Category::
        get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });
        $products = Product::
        whereNull("tax_id")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "category_id" => $item->category_id,
                "sub_category_id" => $item->sub_category_id,
            ];
        });
        $taxes = Tax::
        select("id", "name", "type", "amount")
        ->get();

        return response()->json([
            "products" => $products,
            "taxes" => $taxes,
            "categories" => $categories,
        ]);
    }
 
    public function selecte_products(Request $request){
        $validator = Validator::make($request->all(), [
            'products' => ['required', 'array'],
            'products.*' => ['required', 'exists:products,id'],
            'tax_id' => ["required", "exists:taxes,id"],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        Product::
        where("tax_id", $request->tax_id)
        ->update([
            "tax_id" => null,
        ]);
        
        Product::
        whereIn("id", $request->products)
        ->update([
            "tax_id" => $request->tax_id,
        ]);

        return response()->json([
            "success" => "You select products to this tax success"
        ]);
    } 
}
