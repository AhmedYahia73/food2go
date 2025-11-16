<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseConsumersion;
use App\Models\PurchaseStock;
use App\Models\PurchaseCategory;
use App\Models\PurchaseProduct;
use App\Models\PurchaseStore;
use App\Models\Branch; 

class PurchaseConsumersionController extends Controller
{
    public function __construct(private PurchaseConsumersion $consumersions,
    private PurchaseStock $stock, private PurchaseCategory $categories,
    private PurchaseProduct $products, private PurchaseStore $stores,
    private Branch $branches){}

    public function view(Request $request){
        $consumersions = $this->consumersions
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
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
                'status' => $item->status,
            ];
        }); 

        return response()->json([
            'consumersions' => $consumersions, 
        ]);
    }

    public function lists(Request $request){
        $branches = $this->branches
        ->where('status', 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });
        $categories = $this->categories
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });
        $products = $this->products
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });
        $stores = $this->stores
        ->select('id', 'name')
        ->where('status', 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });

        return response()->json([
            'branches' => $branches,
            'consumersions' => $consumersions,
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

        $consumersions = $this->consumersions
        ->where('id', $id)
        ->first();
        
        if($request->status == 'approve'){
            $stock = $this->stock
            ->where('category_id', $consumersions->category_id)
            ->where('product_id', $consumersions->product_id)
            ->where('store_id', $consumersions->store_id)
            ->first();

            if(empty($stock)){
                $this->stock
                ->create([
                    'category_id' => $request->category_id,
                    'product_id' => $request->product_id,
                    'store_id' => $request->to_store_id,
                    'quantity' => -$request->quintity,
                ]);
            }
            else{
                $stock->quantity -= $request->quintity;
                $stock->save();
            }
        }

        $consumersions->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => $request->status
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
        $consumersionsRequest['status'] = 'approve';
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

    // public function delete(Request $request, $id){
    //     $this->consumersions
    //     ->where('id', $id)
    //     ->delete();

    //     return response()->json([
    //         'success' => 'You delete data success'
    //     ]);
    // }

}
