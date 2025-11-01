<?php

namespace App\Http\Controllers\api\admin\expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ExpenseList;
use App\Models\ExpenseCategory;

class ExpenseListController extends Controller
{
    public function __construct(private ExpenseList $expense,
    private ExpenseCategory $categories){}

    public function view(Request $request){
        $expenses = $this->expense
        ->with("category:id,name")
        ->get();

        return response()->json([
            "expenses" => $expenses, 
        ]);
    }

    public function lists(Request $request){
        $categories = $this->categories
        ->select("id", "name")
        ->where("status", 1)
        ->get();

        return response()->json([
            "categories" => $categories, 
        ]);
    }

    public function expense_item(Request $request, $id){
        $expense = $this->expense
        ->where("id", $id)
        ->with("category:id,name")
        ->first();

        return response()->json([
            "expense" => $expense, 
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

        $this->expense
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
            'category_id' => ['required', 'exists:expense_categories,id'],
            'name' => ['required'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $expenseRequest = $validator->validated();
        $this->expense
        ->create($expenseRequest);

        return response()->json([
            "success" => "You add expense success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
           'category_id' => ['required', 'exists:expense_categories,id'],
            'name' => ['required'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $expenseRequest = $validator->validated();
        $this->expense
        ->where("id", $id)
        ->update($expenseRequest);

        return response()->json([
            "success" => "You update expense success"
        ]);
    }

    public function delete(Request $request, $id){
        $this->expense
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete expense success"
        ]);
    }
}
