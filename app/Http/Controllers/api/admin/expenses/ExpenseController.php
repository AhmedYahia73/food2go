<?php

namespace App\Http\Controllers\api\admin\expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Expense;

class ExpenseController extends Controller
{
    public function __construct(private Expense $expenses){}

    public function view(Request $request){
        $expenses = $this->expenses
        ->with(["expense:id,name", "admin:id,name"
        ,"branch:id,name", "cashier:id,name", "cahier_man:id,name",
        "financial_account:id,name", "category:id,name"])
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount,
                "note" => $item->note,
                "expense" => $item->expense,
                "admin" => $item->admin,
                "branch" => $item->branch,
                "cashier" => $item->cashier,
                "cahier_man" => $item->cahier_man,
                "financial_account" => $item->financial_account,
                "category" => $item->category,
            ];
        });

        return response()->json([
            "expenses" => $expenses, 
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'expense_id' => ['required', 'exists:expense_lists,id'],
            //'admin_id' => ['required', ''],
            'branch_id' => ['required', 'exists:branches,id'],
            'cashier_id' => ['required', 'exists:cashiers,id'],
            'cahier_man_id' => ['required', 'exists:cashier_men,id'],
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
        $expenseRequest['admin_id'] = $request->user()->id;
        $this->expenses
        ->create($expenseRequest);

        return response()->json([
            "success" => "You add expense success"
        ]);
    }
}
