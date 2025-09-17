<?php

namespace App\Http\Controllers\api\storage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseStock;
use App\Models\PurchaseStore;

class StockController extends Controller
{
    public function __construct(private PurchaseStock $stock,
    private PurchaseStore $store){}

    public function view_stock(Request $request, $id){
        $stores = $this->stock
        ->where('store_id', $request->user()->store_id)
        ->with('category', 'product', 'store')
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'category' => $item?->category?->name,
                'product' => $item?->product?->name,
                'store' => $item?->store?->name,
                'quantity' => $item->quantity,
            ];
        });

        return response()->json([
            'stores' => $stores
        ]);
    }
}
