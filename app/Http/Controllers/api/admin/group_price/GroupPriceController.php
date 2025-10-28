<?php

namespace App\Http\Controllers\api\admin\group_price;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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
        ->map(function($item) use($group_product) {
            $price = $item?->group_price?->price ?? null;
            if(empty($price)){
                $price = $group_product->increase_precentage - $group_product->decrease_precentage;
                $price = $item->price + $price * $item->price / 100;
            }
            $status = $item->group_product_status
            ->where("id", $group_product->id)->count()
            > 0;
            return [
                "product_id" => $item->product_id,
                "group_product_id" => $item->group_product_id,
                "product_name" => $item->name,
                "price" => $price,
                'status' => $status
            ];
        });

        return response()->json([
            "products" => $products
        ]);
    }

    public function status(Request $request, $id){
        
    }

    public function price(Request $request, $id){
        
    }
}
