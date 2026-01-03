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
    
    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'module' => 'required|in:take_away,dine_in,delivery',
            'product_id' => 'required|exists:products,id',
            'price' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $product_pricing = ProductPosPricing::
        where("module", $request->module)
        ->where("product_id", $request->product_id)
        ->first();
        if(!empty($product_pricing)){
            $product_pricing->price = $request->price;
            $product_pricing->save();
        }
        else{
            ProductPosPricing::create([
                "module" => $request->module,
                "product_id" => $request->product_id,
                "price" => $request->price,
            ]);
        }

        return response()->json([
            "success" => 'you update price success',
        ]);
    }  
}
