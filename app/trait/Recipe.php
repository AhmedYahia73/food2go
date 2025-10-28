<?php

namespace App\trait;

use App\Models\Product;
use App\Models\PurchaseStock;

use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait Recipe
{ 
    public function pull_recipe($products, $branch_id){
        foreach ($products as $item) {
            $product = Product::
            where("id", $item["id"])
            ->with("unit:id,name")
            ->first();
            if(empty($product)){ 
                return response()->json([
                    "success" => false,
                    "msg" => $item["id"] . " id is wrong"
                ]);
            }
            $stock = PurchaseStock::
            where("product_id", $item["id"])
            ->whereHas("store", function($query) use($branch_id){
                $query->whereHas("branches", function($q) use($branch_id){
                    $q->where("branches.id", $branch_id);
                });
            })
            ->with("unit")
            ->first();
            if($product->recipe && empty($stock)){
                return response()->json([
                    "success" => false,
                    "msg" => $product->name . " is out of stock"
                ]);
            }
            elseif($product->recipe){
                if($product->weight_status){
                    if($product->unit_id == $stock->unit_id){
                        $stock->quantity -= $item['count'];
                        $stock->save();
                    }
                    else{
                        if($stock?->unit?->name == "Kg" && $product?->unit?->name == "Gram"){
                            $count = $item["count"] / 1000;
                        }
                        elseif($stock?->unit?->name == "Gram" && $product?->unit?->name == "Kg"){
                            $count = $item["count"] * 1000;
                        }
                        else{
                            $count = $item["count"];
                        }
                        $stock->quantity -= $count;
                        $stock->save();
                    }
                }
                else{
                    $stock->quantity -= $item['count'];
                    $stock->save();
                }

            }
        } 

        return [
            "success" => true,
        ]; 
    }
}
