<?php

namespace App\Http\Controllers\api\admin\product_pos_pricing;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ProductPosPricing;
use App\Models\Product;

class ProductPOSPricingController extends Controller
{
    public function view($module){
        $product_pricing = Product::
        with("pos_pricing")
        ->get()
        ->map(function($item) use($module){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "price" => $item->pos_pricing
                ->where("module", $module)
                ->first()?->price ?? $item->price,
            ];
        });
        $modules = ['take_away','dine_in','delivery'];

        return response()->json([
            "product_pricing" => $product_pricing,
            "modules" => $modules,
        ]);
    }

    public function price_item($id){
        $product_pricing = Product::
        with("pos_pricing")
        ->where("id", $id)
        ->first(); 

        return response()->json([
            "id" => $product_pricing->id,
            "name" => $product_pricing->name,
            "price" => $product_pricing->pos_pricing
            ->where("module", $module)
            ->first()?->price ?? $product_pricing->price,
        ]);
    }
    
    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'items' => ['required', 'array'],
            'items.*.module' => 'required|in:take_away,dine_in,delivery,all',
            'items.*.product_id' => 'required|exists:products,id',
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
            }
            else{
                $product_pricing = ProductPosPricing::
                where("module", $item['module'])
                ->where("product_id", $item['product_id'])
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
                    ]);
                }
            }
        }

        return response()->json([
            "success" => 'you update price success',
        ]);
    }  
}
