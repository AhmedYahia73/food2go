<?php

namespace App\Http\Controllers\api\admin\group_price;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\GroupProduct;
use App\Models\GroupPrice;
use App\Models\Product;

class GroupPriceController extends Controller
{
    public function __construct(private GroupPrice $group_price,
    private Product $products, private GroupProduct $group_product){}

    public function view(Request $request, $id){
        $group_product = $this->group_product
        ->where("id", $id)
        ->first();
        if(empty($group_product)){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        $products = $this->products 
        ->with(["group_price", "group_product_status"])
        ->get()
        ->map(function($item) use($group_product, $id) {
            $price = $item?->group_price
            ?->where("group_product_id", $id)
            ?->first()?->price ?? null;
            if(empty($price)){
                $price = $group_product->increase_precentage - $group_product->decrease_precentage;
                $price = $item->price + $price * $item->price / 100;
            }
            $status = $item->group_product_status
            ->where("id", $group_product->id)->count()
            <= 0; 
            return [
                "product_id" => $item->id,
                "group_product_id" => $id,
                "product_name" => $item->name,
                "price" => $price,
                'status' => $status
            ];
        });

        return response()->json([
            "products" => $products
        ]);
    }

    public function status(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'exists:products,id'], 
            'group_product_id' => ['required', 'exists:group_products,id'], 
            'status' => ['required', 'boolean'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $product = $this->products 
        ->where("id", $request->product_id)
        ->first();
        $product->group_product_status()->detach($request->group_product_id);
        if(!$request->status){
            $product->group_product_status()->attach($request->group_product_id);
        }

        return response()->json([
            "success" => "You update status success"
        ]);
    }

    public function price(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'exists:products,id'], 
            'group_product_id' => ['required', 'exists:group_products,id'], 
            'price' => ['required', 'numeric'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $group_price = $this->group_price
        ->where('product_id', $request->product_id)
        ->where('group_product_id', $request->group_product_id)
        ->first();
        if(empty($group_price)){
            $this->group_price
            ->create([
                "product_id" => $request->product_id,
                "group_product_id" => $request->group_product_id,
                "price" => $request->price,
            ]);
        }
        else{
            $group_price->price = $request->price;
            $group_price->save();
        }

        return response()->json([
            "success" => "You update prices success"
        ]);
    }
}
