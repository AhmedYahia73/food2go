<?php

namespace App\Http\Controllers\api\admin\inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\MaterialStock;
use App\Models\PurchaseStore;
use App\Models\Material;
use App\Models\MaterialCategory;
use App\Models\InventoryHistory;
use App\Models\InventoryMaterialHistory;
use App\Models\Purchase;

class InventoryMaterialController extends Controller
{
    public function __construct(private PurchaseStore $stores,
    private MaterialStock $stocks, private Material $materials,
    private MaterialCategory $categories, private InventoryHistory $inventory,
    private InventoryMaterialHistory $materials_history,
    private Purchase $purchase){}

    public function lists(Request $request){
        $stores = $this->stores
        ->select("id", "name")
        ->get();
        $materials = $this->materials
        ->select("name", "id", "category_id")
        ->get();
        $categories = $this->categories
        ->select("id", "name")
        ->get();

        return response()->json([
            "stores" => $stores,
            "materials" => $materials,
            "categories" => $categories,
        ]);
    }

    public function view(Request $request){
        $validator = Validator::make($request->all(), [
            'store_id' => 'required|exists:purchase_stores,id',
            "type" => 'required|in:partial,full',
            'materials' => 'array',
            'materials.*' => 'required|exists:materials,id',
            'category_materials' => 'array',
            'category_materials.*' => 'required|exists:material_categories,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $stocks = $this->stocks
        ->where("store_id", $request->store_id)
        ->with("category", "material");
        
        if($request->materials && $request->type == "partial"){
            $stocks = $stocks
            ->whereIn("material_id", $request->materials);
        }
        elseif($request->category_materials && $request->type == "partial"){
            $stocks = $stocks
            ->whereIn("category_id", $request->category_materials);
        }
        $stocks = $stocks
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "quantity" => $item->quantity,
                "actual_quantity" => $item->actual_quantity,
                "inability" => $item->inability,
                "category" => $item?->category?->name,
                "material" => $item?->material?->name,
                "unit" => $item?->unit?->name,
            ];
        });

        return response()->json([
            "stocks" => $stocks,
            "material_count" => $stocks->count(),
        ]);
    }

    public function modify_stocks(Request $request){
        $validator = Validator::make($request->all(), [
            'stocks' => 'required|array',
            'stocks.*.id' => 'required|exists:material_stocks,id',
            'stocks.*.quantity' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $inventory = $this->inventory
        ->create([
            "admin_id" => $request->user()->id,
        ]);
        foreach ($request->stocks as $item) {
            $cost = 0;
            $stock = $this->stocks
            ->where("id", $item['id'])
            ->first();
            $last_purchase_amount = 0;
            $purchase = $this->purchase
            ->where('store_id', $stock->store_id)
            ->where('material_id', $item->material_id)
            ->orderByDesc("id")
            ->get();
            $purchase_arr = [];
            $total_quantity = $item['quantity'] - $stock->quantity;
            foreach ($purchase as $element) {
                $last_purchase_amount = $element->quintity;
                $purchase_arr[] = $element;
                if($element->quintity >= $stock){
                    break;
                }
                $stock -= $element->quintity;
            } 
            foreach ($purchase_arr as $key => $element) {
                $cost_item = $element->total_coast / $element->quintity;
                if($key == 0 && count($purchase_arr) > 1){
                    $cost += $cost_item * $last_purchase_amount;
                }
				elseif(count($purchase_arr) == $key + 1){ 
                    $cost += $cost_item * $total_quantity;
				}
                else{
                    $cost += $cost_item * $element->quintity; 
                }
				$total_quantity -= $element->quintity;
            } 
            $this->materials_history
            ->create([
                'material_id' => $stock->material_id,
                'cost' => $cost,
                'quantity_from' => $stock->quantity,
                'quantity_to' => $item['quantity'],
                'inability' => $item['quantity'] - $stock->quantity,
                'inventory_id' => $inventory->id,
            ]);
            $stock->update([
                "quantity" => $item['quantity'],
                "actual_quantity" => $item['quantity'],
            ]);
        }

        return response()->json([
            "success" => "You update stoks success"
        ]);
    }

    public function modify_actual(Request $request){
        $validator = Validator::make($request->all(), [
            'stocks' => 'required|array',
            'stocks.*.id' => 'required|exists:material_stocks,id',
            'stocks.*.actual_quantity' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        foreach ($request->stocks as $item) {
            $this->stocks
            ->where("id", $item['id'])
            ->update([
                "actual_quantity" => $item['actual_quantity'],
            ]);
        }

        return response()->json([
            "success" => "You update actual quantity success"
        ]);
    }

    public function history(Request $request){
        $material_inventory = $this->inventory
        ->whereHas("materials")
        ->with("admin")
        ->get();
    }
}
