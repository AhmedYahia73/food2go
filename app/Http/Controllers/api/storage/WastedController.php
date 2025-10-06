<?php

namespace App\Http\Controllers\api\storage;

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
        ->where('store_id', $request->user()->store_id)
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

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:purchase_categories,id'],
            'product_id' => ['required', 'exists:purchase_products,id'], 
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
        ->where('store_id', $request->user()->store_id)
        ->first();
        if(empty($stock)){
            $this->stock
            ->create([
                'category_id' => $request->category_id,
                'product_id' => $request->product_id,
                'store_id' => $request->user()->store_id,
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
}
