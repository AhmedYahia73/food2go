<?php

namespace App\Http\Controllers\api\branch\category;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Category;
use App\Models\BranchOff;
use App\Models\Product;

class CategoryController extends Controller
{
    public function categories(){
        $categories = Category::
        where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "status" => $item->category_off
                ->where("branch_id", auth()->user()->id)
                ->count() > 0 ? 0 : 1,
            ];
        });
        
        return response()->json([
            'categories' => $categories
        ]);
    }
     
    public function branch_category_status(Request $request, $id){
        // /branch/branch_category_status/{id}
        // keys
        // status, branch_id
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        if ($request->status) {
            $branch_off = BranchOff::
            where('branch_id', $request->user()->id)
            ->where('category_id', $id)
            ->delete();
        } 
        else {
            BranchOff::
            create([
                'branch_id' => $request->user()->id,
                'category_id' => $id
            ]);
        }
        
        return response()->json([
            'success' => 'You change status success'
        ]);
    }
    
    public function products(){
        $products = Product::
        where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "status" => $item->product_off
                ->where("branch_id", auth()->user()->id)
                ->count() > 0 ? 0 : 1,
            ];
        });
        
        return response()->json([
            'products' => $products
        ]);
    }
     
    public function branch_products_status(Request $request, $id){
        // /branch/branch_products_status/{id}
        // keys
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        if ($request->status) {
            $branch_off = BranchOff::
            where('branch_id', $request->user()->id)
            ->where('product_id', $id)
            ->delete();
        } 
        else {
            BranchOff::
            create([
                'branch_id' => $request->user()->id,
                'product_id' => $id
            ]);
        }
        
        return response()->json([
            'success' => 'You change status success'
        ]);
    }
}
