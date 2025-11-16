<?php

namespace App\Http\Controllers\api\admin\purchases;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PurchaseCategory;

class PurchaseCategoryController extends Controller
{
    public function __construct(private PurchaseCategory $category){}

    public function view(Request $request){
        $categories = $this->category  
        ->with(['category:id,name'])
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'status' => $item->status,
                'category_id' => $item->category_id,
                'category' => $item?->category?->name,
            ];
        });
        $parent_categories = $this->category
        ->whereNull('category_id')
        ->get()
        ->map(function($item){
            return [
                'id' => $item->id,
                'name' => $item->name,
                'status' => $item->status,
            ];
        });

        return response()->json([
            'parent_categories' => $parent_categories,
            'categories' => $categories,
        ]);
    }
    
    public function category(Request $request, $id){ 
        $category = $this->category
        ->where('id', $id)
        ->with('category:id,name')
        ->first();

        return response()->json([
            'category' => $category,
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

        $this->category
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
            'status' => ['required', 'boolean'],
            'category_id' => ['exists:purchase_categories,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $categoryRequest = $validator->validated();
        $category = $this->category
        ->create($categoryRequest);

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'status' => ['required', 'boolean'],
            'category_id' => ['exists:purchase_categories,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $categoryRequest = $validator->validated();
        $category = $this->category
        ->where('id', $id)
        ->first();
        $category->update($categoryRequest);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete(Request $request, $id){
        $this->category
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
