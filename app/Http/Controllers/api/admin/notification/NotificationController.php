<?php

namespace App\Http\Controllers\api\admin\notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PurchaseProduct;
use App\Models\MaterialStock;
use App\Models\Material;
use App\Models\PurchaseStock;

class NotificationController extends Controller
{
    public function __construct(private PurchaseStock $product_stock,
    private MaterialStock $material_stock,){}

    public function stock_product(Request $request){
        $product_stock = $this->product_stock
        ->with("product", "store", "unit")
        ->get()
        ->map(function($item){
            $min_stock = $item?->product?->min_stock ?? 0;
            if($min_stock > $item->quantity){
                return [
                    "id" => $item->id,
                    "category" => $item?->category?->name,
                    "product" => $item?->product?->name,
                    "store" => $item?->store?->name,
                    "quantity" => $item->quantity,
                    "unit" => $item?->unit?->name,
                ];
            }
        })
        ->filter()
        ->values(); 

        return response()->json([
            "product_stock" => $product_stock, 
        ]);
    }

    public function stock_material(Request $request){ 
        $material_stock = $this->material_stock
        ->with("material", "store", "unit")
        ->get()
        ->map(function($item){
            $min_stock = $item?->material?->min_stock ?? 0;
            if($min_stock > $item->quantity){
                return [
                    "id" => $item->id,
                    "category" => $item?->category?->name,
                    "material" => $item?->material?->name,
                    "store" => $item?->store?->name,
                    "quantity" => $item->quantity,
                    "unit" => $item?->unit?->name,
                ];
            }
        })
        ->filter()
        ->values();

        return response()->json([ 
            "material_stock" => $material_stock,
        ]);
    }
}
