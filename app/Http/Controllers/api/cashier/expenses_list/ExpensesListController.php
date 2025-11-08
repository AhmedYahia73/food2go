<?php

namespace App\Http\Controllers\api\cashier\expenses_list;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Expense;

use App\Models\ExpenseList;
use App\Models\ExpenseCategory;
use App\Models\FinantiolAcounting;

class ExpensesListController extends Controller
{
    public function __construct(private Expense $expenses
    , private ExpenseList $expenses_list, private FinantiolAcounting $financial 
    ,private ExpenseCategory $category){}

    public function view(Request $request){
        $expenses = $this->expenses
        ->with(["expense:id,name", "admin:id,name", "cashier:id,name", 
        "financial_account:id,name", "category:id,name"])
        ->where("cahier_man_id", $request->user()->id)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount,
                "note" => $item->note,
                "expense" => $item->expense,
                "admin" => $item->admin, 
                "cashier" => $item->cashier, 
                "financial_account" => $item->financial_account,
                "category" => $item->category,
            ];
        });

        return response()->json([
            "expenses" => $expenses, 
        ]);
    }

    public function lists(Request $request){
        $expenses = $this->expenses_list
        ->select("id", "name")
        ->where("status", 1)
        ->get();
        $financial = $this->financial
        ->select("id", "name")
        ->where("status", 1)
        ->get();  
        $categories = $this->category
        ->select("id", "name")
        ->where("status", 1)
        ->get();

        return response()->json([
            'expenses' => $expenses,  
            'financial' => $financial, 
            'categories' => $categories, 
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'expense_id' => ['required', 'exists:expense_lists,id'],
            'financial_account_id' => ['required', 'exists:finantiol_acountings,id'],
            'category_id' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric'],
            'note' => ['sometimes'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $expenseRequest = $validator->validated();
        $expenseRequest['cahier_man_id'] = $request->user()->id;
        $expenseRequest['cashier_id'] = $request->user()->cashier_id;
        $expenseRequest['branch_id'] = $request->user()->branch_id;
        $this->expenses
        ->create($expenseRequest);

        return response()->json([
            "success" => "You add expense success"
        ]);
    }
}
