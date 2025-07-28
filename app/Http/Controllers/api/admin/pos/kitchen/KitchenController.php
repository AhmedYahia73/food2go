<?php

namespace App\Http\Controllers\api\admin\pos\kitchen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

use App\Models\Kitchen;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Category;

class KitchenController extends Controller
{
    public function __construct(private Kitchen $kitchen,
    private Branch $branches, private Product $products,
    private Category $category){}

    public function view(Request $request){
        // /admin/pos/kitchens
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'errors' => $validator->errors(),
            ],400);
        }
        $kitchens = $this->kitchen
        ->where('branch_id', $request->branch_id)
        ->with('branch', 'products')
        ->where('type', 'kitchen')
        ->get(); 

        return response()->json([
            'kitchens' => $kitchens, 
        ]);
    }
    
    public function brista(Request $request){
        // /admin/pos/kitchens/brista
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'errors' => $validator->errors(),
            ],400);
        }
        $brista = $this->kitchen
        ->where('branch_id', $request->branch_id)
        ->with('branch', 'products')
        ->where('type', 'brista')
        ->get(); 

        return response()->json([
            'brista' => $brista, 
        ]);
    }
    
    public function lists(){
        // /admin/pos/kitchens/lists
        $data = $this->kitchen
        ->with('branch', 'products')
        ->where('status', 1)
        ->get();
        $kitchens = $data
        ->where('type', 'kitchen')
        ->values();
        $brista = $data
        ->where('type', 'brista')
        ->values();
        $branches = $this->branches
        ->get();
        $products = $this->products
        ->whereNull('kitchen_id')
        ->get();
        $category = $this->category
        ->where('status', 1)
        ->get();

        return response()->json([
            'kitchens' => $kitchens,
            'brista' => $brista,
            'branches' => $branches,
            'products' => $products,
            'category' => $category, 
        ]);
    }

    public function select_product(Request $request){
        // /admin/pos/kitchens/select_product
        // product_id[], kitchen_id, category_id[]
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|array',
            'product_id.*' => 'required|exists:products,id',
            'category_id' => 'required|array',
            'category_id.*' => 'required|exists:categories,id',
            'kitchen_id' => 'required|exists:kitchens,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }  
        $kitchen = $this->kitchen
        ->where('id', $request->kitchen_id)
        ->first();
        $kitchen->products()->sync($request->product_id);
        $kitchen->category()->sync($request->category_id);

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
        // Keys
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
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
        // Keys
        // name, password, branch_id, status, type[kitchen, brista]
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'password' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
            'type' => 'required|in:kitchen,brista',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
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
        // Keys
        // name, password, branch_id, status
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'branch_id' => 'required|exists:branches,id',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $kitchenRequest = $validator->validated(); 
        if($request->password){
            $kitchenRequest['password'] = Hash::make($request->password);
        }
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
