<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseConsumersion;
use App\Models\PurchaseStock;

class PurchaseConsumersionController extends Controller
{
    public function __construct(private PurchaseConsumersion $consumersions,
    private PurchaseStock $stock){}

    public function view(Request $request){
        $consumersions = $this->consumersions
        ->get()
        ->map(function($item){
            return [
                'category_id' => $item->category_id,
                'product_id' => $item->product_id,
                'branch_id' => $item->branch_id,
                'store_id' => $item->store_id,
                'admin_id' => $item->admin_id,
                'category' => $item?->category?->name,
                'product' => $item?->product?->name,
                'branch' => $item?->branch?->name,
                'store' => $item?->store?->name,
                'admin' => $item?->admin?->name,
                'quintity' => $item->quintity,
                'date' => $item->date,
            ];
        });

        return response()->json([
            'consumersions' => $consumersions
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'category_id' => ['required', 'exists:purchase_categories,id'],
            'product_id' => ['required', 'exists:purchase_products,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'store_id' => ['required', 'exists:purchase_stores,id'],
            'quintity' => ['required', 'numeric'],
            'date' => ['required', 'date'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $consumersionsRequest = $validator->validated();
        $consumersionsRequest['admin_id'] = $request->user()->id;
        $consumersions = $this->consumersions
        ->create($consumersionsRequest);
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
                'quantity' => -$request->quintity,
            ]);
        }
        else{
            $stock->quantity -= $request->quintity;
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
            'branch_id' => ['required', 'exists:branches,id'],
            'store_id' => ['required', 'exists:purchase_stores,id'],
            'quintity' => ['required', 'numeric'],
            'date' => ['required', 'date'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $consumersionsRequest = $validator->validated();
        $consumersionsRequest['admin_id'] = $request->user()->id;
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
                'quantity' => $consumersions->quintity - $request->quintity,
            ]);
        }
        else{
            $stock->quantity += $consumersions->quintity - $request->quintity;
            $stock->save();
        }
        $consumersions->update($consumersionsRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    } 
}
