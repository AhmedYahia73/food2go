<?php

namespace App\Http\Controllers\api\admin\taxes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Tax;
use App\Models\Product;
use App\Models\Category;
use App\Models\TaxModule;
use App\Models\TaxModuleBranch;

class TaxProductController extends Controller
{
    public function view(Request $request, $id){
        $products = TaxModule::
        where("tax_id", $id)
        ->with("products")
        ->first()
        ?->products;
        if($products && $products->count() > 0){
            $products = $products
            ->map(function($item){
                return [
                    "id" => $item->id,
                    "name" => $item->name,
                    "category_id" => $item->category_id,
                    "sub_category_id" => $item->sub_category_id,
                ];
            });
        }
        else{
            $products = [];
        }

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
            'products' => ['array'],
            'products.*' => ['required', 'exists:products,id'],
            'tax_id' => ["required", "exists:taxes,id"],
            'tax_modules' => ["required", "array"],
            'tax_modules.*' => ["required", "in:take_away,dine_in,delivery"],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $taxes = TaxModule::
        where("tax_id", $request->tax_id)
        ->first();
        $old_tax_module = TaxModule::
        whereHas("products", function($query) use ($request){
            $query->whereIn("products.id", $request->products);
        })
        ->get();
        foreach ($old_tax_module as $element) {
            $element->products()->detach($request->products);
        }
        if(empty($taxes)){
            $tax_module = TaxModule::create([
                "tax_id" => $request->tax_id,
                "status" => 1
            ]);
            $modules = $request->tax_modules;
            foreach ($modules as $item) {
                TaxModuleBranch::create([
                    "tax_module_id" => $tax_module->id,
                    "module" => $item,
                    "type" => "all"
                ]);
            }
            $tax_module->products()->attach($request->products ?? []);
        }
        else{ 
            $modules = $request->tax_modules;
            TaxModuleBranch::
            where("tax_module_id", $taxes->id)
            ->delete();
            foreach ($modules as $item) {
                TaxModuleBranch::create([
                    "tax_module_id" => $taxes->id,
                    "module" => $item,
                    "type" => "all"
                ]);
            }
            $taxes->products()->sync($request->products ?? []);
        } 

        return response()->json([
            "success" => "You select products to this tax success"
        ]);
    } 
}
