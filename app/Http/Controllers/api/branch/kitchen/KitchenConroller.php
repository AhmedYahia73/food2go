<?php

namespace App\Http\Controllers\api\branch\kitchen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

use App\Models\Kitchen;
use App\Models\Product;
use App\Models\Category;

class KitchenConroller extends Controller
{
    public function __construct(private Kitchen $kitchen,
    private Product $products, private Category $category){}

    public function view(Request $request){
        // /admin/pos/kitchens 
        $kitchens = $this->kitchen
        ->where('branch_id', $request->user()->id)
        ->where('type', 'kitchen')
        ->get(); 

        return response()->json([
            'kitchens' => $kitchens, 
        ]);
    }
    
    public function brista(Request $request){
        // /admin/pos/kitchens/brista 
        $brista = $this->kitchen
        ->where('branch_id', $request->user()->id)
        ->where('type', 'brista')
        ->get(); 

        return response()->json([
            'brista' => $brista, 
        ]);
    }

    public function products_in_kitchen(Request $request, $id){
        $products = $this->products
        ->select('name', 'id', 'image')
        ->orderBy('order')
        ->whereHas('kitchen', fn($query) => $query
            ->where('kitchens.id', $id)
        )
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'image' => $item->image_link,
            ];
        });

        return response()->json([
            'products' => $products
        ]);
    }

    public function categories_in_kitchen(Request $request, $id){
        $categories = $this->category
        ->select('name', 'id', 'image')
        ->whereHas('kitchen', fn($query) => $query
            ->where('kitchens.id', $id)
        )
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'image' => $item->image_link,
            ];
        });

        return response()->json([
            'categories' => $categories
        ]);
    }
    
    public function lists(Request $request){
        // /admin/pos/kitchens/lists
        $data = $this->kitchen
        ->where('branch_id', $request->user()->id)
        ->where('status', 1)
        ->get();
        $kitchens = $data
        ->where('type', 'kitchen')
        ->values();
        $brista = $data
        ->where('type', 'brista')
        ->values();
        $products = $this->products
        ->orderBy('order')
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'image' => $item->image_link,
            ];
        });
        $category = $this->category
        ->where('status', 1)
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'image' => $item->image_link,
            ];
        });

        return response()->json([
            'kitchens' => $kitchens,
            'brista' => $brista,
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
        ->where('branch_id', $request->user()->id)
        ->first();
        $kitchen->products()->sync($request->product_id);
        $kitchen->category()->sync($request->category_id);

        return response()->json([
            'success' => 'You add products success'
        ]);
    }

    public function kitchen(Request $request, $id){
        // /admin/pos/kitchens/item/{id}
        $kitchen = $this->kitchen 
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
        ->where('branch_id', $request->user()->id)
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
        // print_status, print_name, print_ip
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:kitchens',
            'password' => 'required', 
            'print_status' => 'required|boolean',
            'print_name' => 'sometimes',
            'print_ip' => 'sometimes',
            'status' => 'required|boolean',
            'type' => 'required|in:kitchen,brista',
            'preparing_time' => 'required|date_format:H:i:s',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $kitchenRequest = $validator->validated();
        $kitchenRequest['branch_id'] = $request->user()->id;
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
            'name' => "required|unique:kitchens,name,$id",
            'print_status' => 'required|boolean',
            'print_name' => 'sometimes',
            'print_ip' => 'sometimes',
            'status' => 'required|boolean',
            'preparing_time' => 'required|date_format:H:i:s',
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
        ->where('branch_id', $request->user()->id)
        ->update($kitchenRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete(Request $request, $id){
        // /admin/pos/kitchens/delete/{id}
        $this->kitchen
        ->where('id', $id)
        ->where('branch_id', $request->user()->id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
