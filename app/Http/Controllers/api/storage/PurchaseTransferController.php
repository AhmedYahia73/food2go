<?php

namespace App\Http\Controllers\api\storage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseTransfer;
use App\Models\PurchaseProduct;
use App\Models\PurchaseCategory;
use App\Models\PurchaseStore;
use App\Models\PurchaseStock;
use App\Models\Unit;

class PurchaseTransferController extends Controller
{
    public function __construct(private PurchaseTransfer $purchases,
    private PurchaseProduct $products, private PurchaseCategory $categories,
    private PurchaseStore $stores, private PurchaseStock $stock,
    private Unit $units){} 

    public function view(Request $request){ 
        $purchases = $this->purchases
        ->with('category', 'product', 'from_store', 'to_store', 'admin')
        ->where('from_store_id', $request->user()->store_id)
        ->orWhere('to_store_id', $request->user()->store_id)
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'from_store_id' => $item->from_store_id,
                'to_store_id' => $item->to_store_id,
                'product_id' => $item->product_id,
                'category_id' => $item->category_id,
                'quintity' => $item->quintity, 
                'to_store' => $item?->to_store?->name,
                'from_store' => $item?->from_store?->name,
                'category' => $item?->category?->name,
                'product' => $item?->product?->name,
                'admin' => $item?->admin?->name,
                'status' => $item->status,
            ];
        });
        $categories = $this->categories
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get();
        $products = $this->products
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get();
        $stores = $this->stores
        ->select('id', 'name')
        ->where('status', 1)
        ->get(); 
        $units = $this->units
        ->select("name", "status")
        ->where("status", 1)
        ->get();

        return response()->json([
            'purchases' => $purchases,
            'categories' => $categories,
            'products' => $products,
            'stores' => $stores, 
            'units' => $units, 
        ]);
    }

    public function transfer(Request $request){
        $validator = Validator::make($request->all(), [ 
            'to_store_id' => ['required', 'exists:purchase_stores,id'], 
            'category_id' => ['required', 'exists:purchase_categories,id'], 
            'product_id' => ['required', 'exists:purchase_products,id'], 
            'quintity' => ['required', 'numeric'], 
            'unit_id' => ['required', 'exists:units,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->purchases
        ->create([
            'from_store_id' => $request->user()->store_id,
            'to_store_id' => $request->to_store_id,
            'category_id' => $request->category_id,
            'product_id' => $request->product_id,
            'admin_id' => $request->user()->id,
            'quintity' => $request->quintity,
            'unit_id' => $request->unit_id,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => 'You update status success'
        ]);
    }
}
