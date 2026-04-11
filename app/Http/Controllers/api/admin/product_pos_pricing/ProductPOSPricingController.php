<?php

namespace App\Http\Controllers\api\admin\product_pos_pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ProductPosPricing;
use App\Models\Product;
use App\Models\Branch;

class ProductPOSPricingController extends Controller
{
    public function lists(){

        $modules = ['take_away','dine_in','delivery'];
        $branches = Branch::where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });
        
        return response()->json([
            "branches" => $branches,
            "modules" => $modules,
        ]);
    }

    public function view($module, $branch_id){
        $product_pricing = Product::
        with("pos_pricing")
        ->get()
        ->map(function($item) use($module, $branch_id){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "price" => $item->pos_pricing
                ->where("module", $module)
                ->where("branch_id", $branch_id)
                ->first()?->price ?? $item->price,
            ];
        });
        $modules = ['take_away','dine_in','delivery'];

        return response()->json([
            "product_pricing" => $product_pricing,
            "modules" => $modules,
        ]);
    }

    public function price_item(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'module' => 'required|in:take_away,dine_in,delivery,all',
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $module = $request->module;
        $branch_id = $request->branch_id;
        $product_pricing = Product::
        with("pos_pricing")
        ->where("id", $id)
        ->first(); 

        return response()->json([
            "id" => $product_pricing->id,
            "name" => $product_pricing->name,
            "price" => $product_pricing->pos_pricing
            ->where("module", $module)
            ->where("branch_id", $branch_id)
            ->first()?->price ?? $product_pricing->price,
        ]);
    }
    
    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array'],
            'items.*.module' => 'required|in:take_away,dine_in,delivery,all',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.branch_id' => 'required|exists:branches,id',
            'items.*.price' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        foreach ($request->items as $item) {
            if($item['module'] == "all"){
                Product::
                where("id", $item['product_id'])
                ->update([
                    "price" => $item['price'],
                ]);
                // ________________ 1 ________________________
                $product_pricing = ProductPosPricing::
                where("module", "take_away")
                ->where("product_id", $item['product_id'])
                ->where("branch_id", $item['branch_id'])
                ->first();
                if(!empty($product_pricing)){
                    $product_pricing->price = $item['price'];
                    $product_pricing->save();
                }
                else{
                    ProductPosPricing::create([
                        "module" => "take_away",
                        "product_id" => $item['product_id'],
                        "price" => $item['price'],
                        "branch_id" => $item['branch_id'],
                    ]);
                }
                // ________________ 2 ________________________
                $product_pricing = ProductPosPricing::
                where("module", "dine_in")
                ->where("product_id", $item['product_id'])
                ->where("branch_id", $item['branch_id'])
                ->first();
                if(!empty($product_pricing)){
                    $product_pricing->price = $item['price'];
                    $product_pricing->save();
                }
                else{
                    ProductPosPricing::create([
                        "module" => "dine_in",
                        "product_id" => $item['product_id'],
                        "price" => $item['price'],
                        "branch_id" => $item['branch_id'],
                    ]);
                }
                // ________________ 3 ________________________
                $product_pricing = ProductPosPricing::
                where("module", "delivery")
                ->where("product_id", $item['product_id'])
                ->where("branch_id", $item['branch_id'])
                ->first();
                if(!empty($product_pricing)){
                    $product_pricing->price = $item['price'];
                    $product_pricing->save();
                }
                else{
                    ProductPosPricing::create([
                        "module" => "delivery",
                        "product_id" => $item['product_id'],
                        "price" => $item['price'],
                        "branch_id" => $item['branch_id'],
                    ]);
                }
            }
            else{
                $product_pricing = ProductPosPricing::
                where("module", $item['module'])
                ->where("product_id", $item['product_id'])
                ->where("branch_id", $item['branch_id'])
                ->first();
                if(!empty($product_pricing)){
                    $product_pricing->price = $item['price'];
                    $product_pricing->save();
                }
                else{
                    ProductPosPricing::create([
                        "module" => $item['module'],
                        "product_id" => $item['product_id'],
                        "price" => $item['price'],
                        "branch_id" => $item['branch_id'],
                    ]);
                }
            }
        }

        return response()->json([
            "success" => 'you update price success',
        ]);
    }  
}
