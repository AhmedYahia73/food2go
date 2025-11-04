<?php

namespace App\Http\Controllers\api\admin\expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ExpenseCategory;

class ExpenseCategoryController extends Controller
{
    public function __construct(private ExpenseCategory $category){}

    public function view(Request $request){
        $categories = $this->category
        ->select("id", "name", "status")
        ->get();

        return response()->json([
            "categories" => $categories, 
        ]);
    }

    public function category_item(Request $request, $id){
        $category = $this->category
        ->select("name", "status")
        ->where("id", $id)
        ->first();

        return response()->json([
            "category" => $category, 
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
        ->where("id", $id)
        ->update([
            "status" => $request->status
        ]);

        return response()->json([
            "success" => "You update status success", 
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $categoryRequest = $validator->validated();
        $this->category
        ->create($categoryRequest);

        return response()->json([
            "success" => "You add category success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $categoryRequest = $validator->validated();
        $this->category
        ->where("id", $id)
        ->update($categoryRequest);

        return response()->json([
            "success" => "You update category success"
        ]);
    }

    public function delete(Request $request, $id){
        $this->category
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete category success"
        ]);
    }
}
