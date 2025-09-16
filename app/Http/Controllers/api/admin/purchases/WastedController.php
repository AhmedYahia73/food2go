<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseWasted;
use App\Models\PurchaseCategory;
use App\Models\PurchaseProduct;
use App\Models\PurchaseStore; 
use App\Models\PurchaseStock;

class WastedController extends Controller
{
    public function __construct(private PurchaseWasted $wested,
    private PurchaseProduct $products, private PurchaseCategory $categories,
    private PurchaseStock $stock, private PurchaseStore $stores){} 

    public function view(Request $request){
        $wested = $this->wested
        ->with('category', 'product', 'store')
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'category' => $item?->category?->name,
                'product' => $item?->product?->name,
                'store' => $item?->store?->name,
                'category_id' => $item?->category_id,
                'product_id' => $item?->product_id,
                'store_id' => $item?->store_id,
                'quantity' => $item->quantity,
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
            'wested' => $wested,
            'categories' => $categories,
            'products' => $products,
            'stores' => $stores,
        ]);
    }
    
    public function wested(Request $request, $id){ 
        $wested = $this->wested
        ->with('category', 'product', 'store')
        ->where('id', $id)
        ->first(); 

        return response()->json([
            'id' => $wested?->id,
            'category' => $wested?->category?->name,
            'product' => $wested?->product?->name,
            'store' => $wested?->store?->name,
            'category_id' => $wested?->category_id,
            'product_id' => $wested?->product_id,
            'store_id' => $wested?->store_id,
            'quantity' => $wested?->quantity,
            'status' => $wested?->status,
        ]);
    }


    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'in:pending,approve,reject'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $wested = $this->wested
        ->where('id', $id)
        ->first();
        if($request->status == 'approve'){
            $stock = $this->stock
            ->where('product_id', $wested->product_id)
            ->where('store_id', $wested->store_id)
            ->first();
            if(empty($stock)){
                $this->stock
                ->create([
                    'category_id' => $wested->category_id,
                    'product_id' => $wested->product_id,
                    'store_id' => $wested->store_id,
                    'quantity' => -$wested->quantity,
                ]);
            }
            else{
                $stock->quantity -= $wested->quantity;
                $stock->save();
            }
        }
        $wested->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status' => $request->status
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:purchase_categories,id'],
            'product_id' => ['required', 'exists:purchase_products,id'],
            'store_id' => ['required', 'exists:purchase_stores,id'], 
            'quantity' => ['required', 'numeric'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $westedRequest = $validator->validated();
        $westedRequest['status'] = 'approve';
        $wested = $this->wested
        ->create($westedRequest);
        $stock = $this->stock
        ->where('product_id', $request->product_id)
        ->where('store_id', $request->store_id)
        ->first();
        if(empty($stock)){
            $this->stock
            ->create([
                'category_id' => $request->category_id,
                'product_id' => $request->product_id,
                'store_id' => $request->store_id,
                'quantity' => -$request->quantity,
            ]);
        }
        else{
            $stock->quantity -= $request->quantity;
            $stock->save();
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:purchase_categories,id'],
            'product_id' => ['required', 'exists:purchase_products,id'],
            'store_id' => ['required', 'exists:purchase_stores,id'], 
            'quantity' => ['required', 'numeric'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $westedRequest = $validator->validated(); 

        $wested = $this->wested
        ->where('id', $id)
        ->first();
        $consumersions = $this->consumersions
        ->where('id', $id)
        ->first();
        $stock = $this->stock
        ->where('product_id', $request->product_id)
        ->where('store_id', $request->store_id)
        ->first();
        if(empty($stock)){
            $this->stock
            ->create([
                'category_id' => $request->category_id,
                'product_id' => $request->product_id,
                'store_id' => $request->store_id,
                'quantity' => $wested->quantity - $request->quantity,
            ]);
        }
        else{
            $stock->quantity += $wested->quantity - $request->quantity;
            $stock->save();
        }
        $wested->update($westedRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    } 
}
