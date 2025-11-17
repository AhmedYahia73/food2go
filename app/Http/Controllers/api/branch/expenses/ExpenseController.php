<?php

namespace App\Http\Controllers\api\branch\expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Expense;

use App\Models\ExpenseList;
use App\Models\Cashier;
use App\Models\CashierMan;
use App\Models\ExpenseCategory;
use App\Models\FinantiolAcounting;

class ExpenseController extends Controller
{
    public function __construct(private Expense $expenses
    , private ExpenseList $expenses_list 
    , private Cashier $cashiers ,private CashierMan $cashier_man
    , private FinantiolAcounting $financial ,private ExpenseCategory $category){}

    public function view(Request $request){
        $expenses = $this->expenses
        ->with(["expense:id,name", "admin:id,name"
        , "cashier:id,name", "cahier_man:id,user_name",
        "financial_account:id,name", "category:id,name"])
        ->where('branch_id', $request->user()->id)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount,
                "note" => $item->note,
                "expense" => $item->expense,
                "admin" => $item->admin, 
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

    public function lists(Request $request){
        $expenses = $this->expenses_list
        ->select("id", "name")
        ->where("status", 1)
        ->get();
        $cashiers = $this->cashiers
        ->select("id", "name")
        ->where("status", 1)
        ->where("branch_id", $request->user()->branch_id)
        ->get();
        $cashier_man = $this->cashier_man
        ->select("id", "user_name")
        ->where("branch_id", $request->user()->branch_id)
        ->where("status", 1)
        ->get();  
        $financial = $this->financial
        ->select("id", "name")
        ->whereHas('branch', function($query) use($request){
            $query->where('branches.id', $request->user()->id);
        })
        ->where("status", 1)
        ->get();  
        $categories = $this->category
        ->select("id", "name")
        ->where("status", 1)
        ->get();

        return response()->json([
            'expenses' => $expenses,
            'cashiers' => $cashiers,
            'cashier_man' => $cashier_man,
            'financial' => $financial,
            'categories' => $categories,
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'expense_id' => ['required', 'exists:expense_lists,id'],
            //'admin_id' => ['required', ''],
            //'branch_id' => ['required', 'exists:branches,id'],
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
        $expenseRequest['branch_id'] = $request->user()->id;
        $this->expenses
        ->create($expenseRequest);

        $financial = FinantiolAcounting::
        where("id", $request->financial_account_id)
        ->first();
        if($financial){
            $financial->balance -= $request->amount;
            $financial->save();
        }

        return response()->json([
            "success" => "You add expense success"
        ]);
    }

    public function expenses_report(Request $request){
        $validator = Validator::make($request->all(), [
            'from' => ['date'],
            'to' => ['date'],
            'cashier_id' => ['exists:cashiers,id'],
            'cahier_man_id' => ['exists:cashier_men,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $expenses = $this->expenses;
        if($request->from){
            $expenses = $expenses
            ->whereDate("created_at", ">=", $request->from);
        }
        if($request->to){
            $expenses = $expenses
            ->whereDate("created_at", "<=", $request->to);
        }
        if($request->cashier_id){
            $expenses = $expenses
            ->where("cashier_id", $request->cashier_id);
        }
        if($request->cahier_man_id){
            $expenses = $expenses
            ->where("cahier_man_id", $request->cahier_man_id);
        }
        $expenses_lists = $expenses
        ->with(["expense:id,name", "admin:id,name"
        , "cashier:id,name", "cahier_man:id,user_name",
        "financial_account:id,name", "category:id,name"])
        ->where('branch_id', $request->user()->id)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount,
                "note" => $item->note,
                "expense" => $item->expense,
                "admin" => $item->admin, 
                "cashier" => $item->cashier,
                "cahier_man" => $item->cahier_man,
                "financial_account" => $item->financial_account,
                "category" => $item->category,
            ];
        });
        $financials = $expenses
        ->selectRaw("SUM(amount) AS TotalAmount, financial_account_id")
        ->with(["financial_account:id,name"])
        ->groupBy("financial_account_id")
        ->get();

        return response()->json([
            "expenses_lists" => $expenses_lists,
            "financials" => $financials,
        ]);
    }
}
