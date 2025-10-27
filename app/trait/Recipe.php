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
    public function pull_recipe($old_status, $products, $branch_id){
        if($old_status == "pending"){
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
                ->first();// branch_id
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
        }

        return response()->json([
            "success" => true,
        ]); 
    }
}
