<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseTransfer;
use App\Models\PurchaseProduct;
use App\Models\PurchaseCategory;
use App\Models\PurchaseStore;
use App\Models\PurchaseStock;

class PurchaseTransferController extends Controller
{
    public function __construct(private PurchaseTransfer $purchases,
    private PurchaseProduct $products, private PurchaseCategory $categories,
    private PurchaseStore $stores, private PurchaseStock $stock){} 

    public function view(Request $request){ 
        $purchases = $this->purchases
        ->with('category', 'product', 'from_store', 'to_store')
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'from_store_id' => $item->from_store_id,
                'to_store_id' => $item->to_store_id,
                'product_id' => $item->product_id,
                'category_id' => $item->category_id,
                'quantity' => $item->quantity, 
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

        return response()->json([
            'purchases' => $purchases,
            'categories' => $categories,
            'products' => $products,
            'stores' => $stores, 
        ]);
    }

    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:approve,reject'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $purchases = $this->purchases
        ->where('id', $id)
        ->first();
        if($request->status == 'approve'){
            $from_store = $this->stock
            ->where('store_id', $request->from_store_id)
            ->where('product_id', $request->product_id)
            ->first();
            $to_store = $this->stock
            ->where('store_id', $request->to_store_id)
            ->where('product_id', $request->product_id)
            ->first();
            if(empty($from_store)){
                $this->stock
                ->create([
                    'category_id' => $request->category_id,
                    'product_id' => $request->product_id,
                    'store_id' => $request->from_store_id,
                    'quantity' => -$request->quintity,
                ]);
            }
            else{
                $stock = $this->stock
                ->where('category_id', $request->category_id)
                ->where('product_id', $request->product_id)
                ->where('store_id', $request->from_store_id)
                ->first();
                $stock->quantity -= $request->quintity;
                $stock->save();
            }

            if(empty($to_store)){
                $this->stock
                ->create([
                    'category_id' => $request->category_id,
                    'product_id' => $request->product_id,
                    'store_id' => $request->to_store_id,
                    'quantity' => $request->quintity,
                ]);
            }
            else{
                $stock = $this->stock
                ->where('category_id', $request->category_id)
                ->where('product_id', $request->product_id)
                ->where('store_id', $request->to_store_id)
                ->first();
                $stock->quantity += $request->quintity;
                $stock->save();
            }
            $purchases->status = $request->status;
            $purchases->save();
        }

        return response()->json([
            'success' => 'You update status success'
        ]);
    }

    public function transfer(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'from_store_id' => ['required', 'exists:purchase_stores,id'], 
            'to_store_id' => ['required', 'exists:purchase_stores,id'], 
            'category_id' => ['required', 'exists:purchase_categories,id'], 
            'product_id' => ['required', 'exists:purchase_products,id'], 
            'quintity' => ['required', 'numeric'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->purchases
        ->create([
            'from_store_id' => $request->from_store_id,
            'to_store_id' => $request->to_store_id,
            'category_id' => $request->category_id,
            'product_id' => $request->product_id,
            'admin_id' => $request->user()->id,
            'quintity' => $request->quintity,
        ]);
        // stock
        $from_store = $this->stock
        ->where('store_id', $request->from_store_id)
        ->where('product_id', $request->product_id)
        ->first();
        $to_store = $this->stock
        ->where('store_id', $request->to_store_id)
        ->where('product_id', $request->product_id)
        ->first();
        if(empty($from_store)){
            $this->stock
            ->create([
                'category_id' => $request->category_id,
                'product_id' => $request->product_id,
                'store_id' => $request->from_store_id,
                'quantity' => -$request->quintity,
            ]);
        }
        else{
            $stock = $this->stock
            ->where('category_id', $request->category_id)
            ->where('product_id', $request->product_id)
            ->where('store_id', $request->from_store_id)
            ->first();
            $stock->quantity -= $request->quintity;
            $stock->save();
        }

        if(empty($to_store)){
            $this->stock
            ->create([
                'category_id' => $request->category_id,
                'product_id' => $request->product_id,
                'store_id' => $request->to_store_id,
                'quantity' => $request->quintity,
            ]);
        }
        else{
            $stock = $this->stock
            ->where('category_id', $request->category_id)
            ->where('product_id', $request->product_id)
            ->where('store_id', $request->to_store_id)
            ->first();
            $stock->quantity += $request->quintity;
            $stock->save();
        }

        return response()->json([
            'success' => 'You update status success'
        ]);
    }
}
