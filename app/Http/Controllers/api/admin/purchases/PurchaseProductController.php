<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseProduct;
use App\Models\PurchaseCategory;
use App\Models\PurchaseStock;

class PurchaseProductController extends Controller
{
    public function __construct(private PurchaseProduct $product,
    private PurchaseCategory $categories, private PurchaseStock $stock){}

    public function view(Request $request){
        $product = $this->product 
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'status' => $item->status,
                'category_id' => $item->category_id,
                'category' => $item?->category?->name,
                'min_stock' => $item->min_stock,
            ];
        }); 
        $categories = $this->categories
        ->select('id', 'name', 'category_id')
        ->where('status', 1)
        ->get();

        return response()->json([
            'products' => $product,
            'categories' => $categories,
        ]);
    }

    public function product_stock(Request $request, $id){
        $stocks = $this->stock
        ->where("product_id", $id)
        ->with("store", "unit")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "product" => $item?->product?->name,
                "quantity" => $item->quantity,
                "store" => $item?->store?->name,
            ];
        });

        return response()->json([
            "stocks" => $stocks,
        ]);
    }
    
    public function product_item(Request $request, $id){ 
        $product = $this->product
        ->where('id', $id)
        ->with('category:id,name')
        ->get()
        ->first();

        return response()->json([
            'product' => $product,
        ]);
    }

    public function status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => ['required', 'boolean'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->product
        ->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        return response()->json([
            'status' => $request->status ? 'active' : 'banned'
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'description' => ['sometimes'],
            'status' => ['required', 'boolean'],
            'category_id' => ['required', 'exists:purchase_categories,id'],
            'min_stock' => ['required', 'numeric'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $productRequest = $validator->validated();
        $product = $this->product
        ->create($productRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'description' => ['sometimes'],
            'status' => ['required', 'boolean'],
            'category_id' => ['required', 'exists:purchase_categories,id'],
            'min_stock' => ['required', 'numeric'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $productRequest = $validator->validated();
        $product = $this->product
        ->where('id', $id)
        ->first();
        $product->update($productRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete(Request $request, $id){
        $this->product
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
