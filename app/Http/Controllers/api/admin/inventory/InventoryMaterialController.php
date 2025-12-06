<?php

namespace App\Http\Controllers\api\admin\inventory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\MaterialStock;
use App\Models\PurchaseStore;
use App\Models\Material;
use App\Models\MaterialCategory;

class InventoryMaterialController extends Controller
{
    public function __construct(private PurchaseStore $stores,
    private MaterialStock $stocks, private Material $materials,
    private MaterialCategory $categories){}

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
            'materials' => 'required|array',
            'materials.*' => 'required|exists:materials,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $stocks = $this->stocks
        ->where("store_id", $request->store_id)
        ->whereIn("material_id", $request->materials)
        ->with("category", "material")
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
            "stocks" => $stocks
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

        foreach ($request->stocks as $item) {
            $this->stocks
            ->where("id", $item['id'])
            ->update([
                "quantity" => $item['quantity'],
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
}
