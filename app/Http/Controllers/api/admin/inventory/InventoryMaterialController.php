<?php

namespace App\Http\Controllers\api\admin\inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\MaterialStock;
use App\Models\PurchaseStore;
use App\Models\Material;
use App\Models\MaterialCategory; 
use App\Models\InventoryMaterialHistory;
use App\Models\Purchase;
use App\Models\InventoryList;
use App\Models\PurchaseWasted;

class InventoryMaterialController extends Controller
{
    public function __construct(private PurchaseStore $stores,
    private MaterialStock $stocks, private Material $materials,
    private MaterialCategory $categories,
    private InventoryMaterialHistory $materials_history,
    private Purchase $purchase, private InventoryList $inventory_list){}

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

    public function inventory_history(Request $request){
        $inventory_list = $this->inventory_list
        ->orderByDesc("id")
        ->with("store")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "store" => $item?->store?->name,
                "product_num" => $item->product_num,
                "total_quantity" => $item->total_quantity,
                "cost" => $item->cost,
                "date" => $item->created_at,
                "status" => $item->status,
            ];
        });

        return response()->json([
            "inventory_list" => $inventory_list, 
        ]);
    } 

    public function create_inventory(Request $request){
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

        $inventory = InventoryList::
        create([
            "store_id" => $request->store_id,
        ]);
        $all_quantity = 0;
        $materials_arr = [];
        $materials = collect([]);
        if($request->materials && count($request->materials) > 0){
            $materials = $request->materials;
            $materials = Material::
            select("id", "name")
            ->whereIn("id", $materials)
            ->get();
        }
        elseif($request->category_materials && count($request->category_materials) > 0){
            $materials = Material::
            select("id", "name")
            ->whereIn("category_id", $request->category_materials)
            ->get();
        }
        foreach ($materials as $item) {
            $stock = $this->stocks
            ->where("material_id", $item->id)
            ->first();
            $stock_quintity = $stock?->quintity ?? 0;
            $all_quantity += $stock_quintity;
            $material_inventory = InventoryMaterialHistory::
            create([
                'category_id' => $item->category_id,
                'material_id' => $item->id,
                'inventory_id' => $inventory->id,
                'quantity' => $stock_quintity, 
                'actual_quantity' => $stock_quintity, 
                'inability' => 0,
                'cost' => 0,
            ]);
        }  
        $inventory->product_num = count($materials_arr);
        $inventory->total_quantity = $all_quantity;
        $inventory->save();

        return response()->json([
            "stocks" => $stock,
            "inventory" => $inventory,
        ]);
    }

    public function open_inventory(Request $request, $id){
        $materials = InventoryMaterialHistory::
        where("inventory_id", $id)
        ->with("category", "material")
        ->get()
        ->map(function($item){
            return [
                "category" => $item?->category?->name,
                "material" => $item?->material?->name,
                "quantity" => $item?->quantity, 
                "inability" => $item?->inability,
                "cost" => $item?->cost,
            ];
        }); 

        return response()->json([
            "materials" => $materials
        ]);
    }

    public function modify_materials(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'materials' => 'required|array',
            'materials.*.id' => 'required|exists:materials,id',
            'materials.*.quantity' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
 
        InventoryList::
        where("id", $id)
        ->update([
            "status" => "final"
        ]);
        foreach ($request->materials as $item) {
            $cost = 0;
            $stock = $this->stocks
            ->where("material_id", $item['id'])
            ->first();
            $stock_quintity = $stock->quintity ?? 0;
            $last_purchase_amount = 0;
            $purchase = $this->purchase
            ->where('store_id', $stock->store_id)
            ->where('material_id', $item['id'])
            ->orderByDesc("id")
            ->get();
            $purchase_arr = [];
            $total_quantity = $stock_quintity - $item['quantity'];
            foreach ($purchase as $element) {
                $last_purchase_amount = $element->quintity;
                $purchase_arr[] = $element;
                if($element->quintity >= $stock_quintity){
                    break;
                }
                $stock_quintity -= $element->quintity;
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
            InventoryMaterialHistory::
            where("inventory_id", $id)
            ->where("material_id", $item['id'])
            ->update([
                'quantity' => $item['quantity'],
                'cost' => $cost,
                'inability' => $total_quantity,
            ]); 
        }

        return response()->json([
            "success" => "You update stoks success"
        ]);
    }

    public function inability_list(Request $request, $id){
        $inability = InventoryMaterialHistory::
        where("inventory_id", $id)
        ->whereColumn('actual_quantity', '>', 'quantity')
        ->with("category", "material")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "category" => $item?->category?->name,
                "material" => $item?->material?->name,
                "quantity" => $item->quantity,
                "actual_quantity" => $item->actual_quantity,
                "inability" => $item->inability,
                "cost" => $item->cost,
            ];
        });

        return response()->json([
            "shourtage_list" => $inability
        ]);
    }

    public function wested(Request $request){
        $validator = Validator::make($request->all(), [
            'reason' => ["required"],
            'inabilities' => ['required', "array"],
            "inabilities.*" => ['required', "exists:inventory_material_histories,id"], 
            'store_id' => ['required', 'exists:purchase_stores,id'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        if(empty($request->material_id) && empty($request->product_id)){
            return response()->json([
                "errors" => "You must enter material_id or product_id"
            ], 400);
        }
 
        foreach ($request->inabilities as $item) {
            $inventory_material = InventoryMaterialHistory::
            where("id", $item)
            ->first();
            $wested = $inventory_material->inability; 
            $inventory_material->update([
                "actual_quantity" => $inventory_material->quantity
            ]);
        
            $material_stock = $this->material_stock
            ->where('material_id', $item)
            ->where('store_id', $request->store_id)
            ->first();
            $westedRequest['status'] = 'approve';
            $wested = PurchaseWasted::
            create([ 
                'store_id' => $request->store_id, 
                'quantity' => $wested,
                'status' => "approve",
                'material_id' => $inventory_material->material_id,
                'category_material_id' => $inventory_material->category_id,
                'reason' => $request->reason,
            ]);
            
            if(empty($material_stock)){
                $this->material_stock
                ->create([
                    'category_id' => $inventory_material->category_id,
                    'material_id' => $inventory_material->material_id,
                    'store_id' => $request->store_id,
                    'quantity' => $inventory_material->quantity,
                    'actual_quantity' => $inventory_material->quantity,
                ]);
            }
            else{
                $material_stock->quantity = $inventory_material->quantity;
                $material_stock->actual_quantity = $inventory_material->quantity;
                $material_stock->save();
            } 
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    // public function modify_stocks(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'stocks' => 'required|array',
    //         'stocks.*.id' => 'required|exists:material_stocks,id',
    //         'stocks.*.quantity' => 'required|numeric',
    //     ]);
    //     if ($validator->fails()) { // if Validate Make Error Return Message Error
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ],400);
    //     }

    //     $inventory = $this->inventory
    //     ->create([
    //         "admin_id" => $request->user()->id,
    //     ]);
    //     foreach ($request->stocks as $item) {
    //         $cost = 0;
    //         $stock = $this->stocks
    //         ->where("id", $item['id'])
    //         ->first();
    //         $stock_quintity = $stock->quintity;
    //         $last_purchase_amount = 0;
    //         $purchase = $this->purchase
    //         ->where('store_id', $stock->store_id)
    //         ->where('material_id', $stock->material_id)
    //         ->orderByDesc("id")
    //         ->get();
    //         $purchase_arr = [];
    //         $total_quantity = $item['quantity'] - $stock_quintity;
    //         foreach ($purchase as $element) {
    //             $last_purchase_amount = $element->quintity;
    //             $purchase_arr[] = $element;
    //             if($element->quintity >= $stock_quintity){
    //                 break;
    //             }
    //             $stock_quintity -= $element->quintity;
    //         } 
    //         foreach ($purchase_arr as $key => $element) {
    //             $cost_item = $element->total_coast / $element->quintity;
    //             if($key == 0 && count($purchase_arr) > 1){
    //                 $cost += $cost_item * $last_purchase_amount;
    //             }
	// 			elseif(count($purchase_arr) == $key + 1){ 
    //                 $cost += $cost_item * $total_quantity;
	// 			}
    //             else{
    //                 $cost += $cost_item * $element->quintity; 
    //             }
	// 			$total_quantity -= $element->quintity;
    //         } 
    //         $this->materials_history
    //         ->create([
    //             'material_id' => $stock->material_id,
    //             'cost' => $cost,
    //             'quantity_from' => $stock->quantity,
    //             'quantity_to' => $item['quantity'],
    //             'inability' => $item['quantity'] - $stock->quantity,
    //             'inventory_id' => $inventory->id,
    //         ]);
    //         $stock->update([
    //             "quantity" => $item['quantity'],
    //             "actual_quantity" => $item['quantity'],
    //         ]);
    //     }

    //     return response()->json([
    //         "success" => "You update stoks success"
    //     ]);
    // }

    // public function modify_actual(Request $request){
    //     $validator = Validator::make($request->all(), [
    //         'stocks' => 'required|array',
    //         'stocks.*.id' => 'required|exists:material_stocks,id',
    //         'stocks.*.actual_quantity' => 'required|numeric',
    //     ]);
    //     if ($validator->fails()) { // if Validate Make Error Return Message Error
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ],400);
    //     }

    //     foreach ($request->stocks as $item) {
    //         $this->stocks
    //         ->where("id", $item['id'])
    //         ->update([
    //             "actual_quantity" => $item['actual_quantity'],
    //         ]);
    //     }

    //     return response()->json([
    //         "success" => "You update actual quantity success"
    //     ]);
    // }

    // public function history(Request $request){
    //     $material_inventory = $this->inventory
    //     ->whereHas("materials")
    //     ->with("admin")
    //     ->get()
    //     ->map(function($item){
    //         return [
    //             "id" => $item->id,
    //             "date" => $item->created_at->format("Y-m-d"),
    //             "time" => $item->created_at->format("H:i"),
    //             "admin" => $item?->admin?->name,
    //         ];
    //     });

    //     return response()->json([
    //         "material_inventory" => $material_inventory
    //     ]);
    // }

    // public function history_details(Request $request){

    // }
}
