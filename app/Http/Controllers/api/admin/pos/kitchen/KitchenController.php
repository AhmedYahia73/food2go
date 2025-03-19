<?php

namespace App\Http\Controllers\api\admin\pos\kitchen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Kitchen;
use App\Models\Branch;
use App\Models\Product;

class KitchenController extends Controller
{
    public function __construct(private Kitchen $kitchen,
    private Branch $branches, private Product $products){}

    public function view(){
        // /admin/pos/kitchens
        $kitchens = $this->kitchen
        ->with('branch', 'products')
        ->get(); 

        return response()->json([
            'kitchens' => $kitchens, 
        ]);
    }
    
    public function lists(){
        // /admin/pos/kitchens/lists
        $kitchens = $this->kitchen
        ->with('branch', 'products')
        ->where('status', 1)
        ->get();
        $branches = $this->branches
        ->get();
        $products = $this->products
        ->whereNull('kitchen_id')
        ->get();

        return response()->json([
            'kitchens' => $kitchens,
            'branches' => $branches,
            'products' => $products,
        ]);
    }

    public function select_product(Request $request){
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|array',
            'product_id.*' => 'required|exists:products,id',
            'kitchen_id' => 'required|exists:kitchens,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        foreach ($request->product_id as $item) {
            $this->products
            ->where('id', $item)
            ->update([
                'kitchen_id' => $request->kitchen_id
            ]);
        }

        return response()->json([
            'success' => 'You add products success'
        ]);
    }

    public function kitchen($id){
        // /admin/pos/kitchens/item/{id}
        $kitchen = $this->kitchen
        ->with('branch', 'products')
        ->where('id', $id)
        ->first();

        return response()->json([
            'kitchen' => $kitchen
        ]);
    }

    public function status(Request $request, $id){
        // /admin/pos/kitchens/status/{id}
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $kitchen = $this->kitchen
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'success' => $request->status ? 'active': 'banned'
        ]);
    }

    public function create(Request $request){
        // /admin/pos/kitchens/add
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $kitchenRequest = $validator->validated();
        $this->kitchen
        ->create($kitchenRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        // /admin/pos/kitchens/update/{id}
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $kitchenRequest = $validator->validated();
        $this->kitchen
        ->where('id', $id)
        ->update($kitchenRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // /admin/pos/kitchens/delete/{id}
        $this->kitchen
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
