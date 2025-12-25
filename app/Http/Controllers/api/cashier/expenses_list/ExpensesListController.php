<?php

namespace App\Http\Controllers\api\cashier\expenses_list;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\Expense; 
  
use App\Models\ExpenseCategory;
use App\Models\FinantiolAcounting;
use App\Models\OrderFinancial;
use App\Models\Order;  
use App\Models\CashierShift;
use App\Models\TimeSittings;
use App\Models\Setting;

class ExpensesListController extends Controller
{
    public function __construct(private Expense $expenses, 
    private FinantiolAcounting $financial, private ExpenseCategory $category,
    private CashierShift $cashier_shift,
    private Setting $settings, private TimeSittings $TimeSittings){}

    public function view(Request $request){ 

        $locale = $request->locale ?? "en";
        $expenses = $this->expenses
        ->with(["admin:id,name", "cashier:id,name", 
        "financial_account:id,name", "category:id,name"])
        ->where("cahier_man_id", $request->user()->id)
        ->where('shift', $request->user()->shift_number)
        ->orderByDesc("id")
        ->get()
        ->map(function($item) use($locale){
            return [
                "id" => $item->id,
                "amount" => $item->amount,
                "note" => $item->note,
                "expense" => $item->expense,
                "admin" => $item->admin, 
                "cashier" =>  [
                    "id" => $item?->cashier?->id,
                    'name' => $item?->cashier
                    ?->translations()
                    ?->where("locale", $locale)
                    ?->where('key', $item?->cashier?->name)
                    ?->first()
                    ?->value ?? $item?->cashier?->name ?? null,
                ],
                "financial_account" => $item->financial_account,
                "category" =>  [
                    "id" => $item?->category?->id,
                    'name' => $item?->category
                    ?->translations()
                    ?->where("locale", $locale)
                    ?->where('key', $item?->category?->name)
                    ?->first()
                    ?->value ?? $item?->category?->name ?? null,
                ],
            ];
        });

        return response()->json([
            "expenses" => $expenses, 
        ]);
    }

    public function lists(Request $request){
        $locale = $request->locale ?? "en";

        $financial = $this->financial
        ->select("id", "name")
        ->where("status", 1)
        ->get();  
        $categories = $this->category
        ->select("id", "name")
        ->where("status", 1)
        ->get()
        ->map(function($item) use($locale){
            return [
                "id" => $item->id,
                "name" => $item
                ?->translations()
                ?->where("locale", $locale)
                ?->where('key', $item->name)
                ?->first()
                ?->value ?? $item->name ?? null
                ,
            ];
        });

        return response()->json([   
            'financial' => $financial, 
            'categories' => $categories, 
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'expense' => ['required'],
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
        // ___________________________________
    
        $orders = Order::
        select("id")
        ->where('cashier_man_id', $request->user()->id)
        ->where('shift', $request->user()->shift_number)
        ->pluck('id')
        ->toArray();
        
        $shift = $this->cashier_shift
        ->where('shift', $request->user()->shift_number)
        ->where('cashier_man_id', $request->user()->id)
        ->first();
        $expenses = $this->expenses
        ->where('created_at', '>=', $shift->start_time ?? now())
        ->where('created_at', '<=', $shift->end_time ?? now())
        ->where("financial_account_id", $request->financial_account_id)
        ->sum('amount');
        
        $financial_accounts = OrderFinancial:: 
        whereIn("order_id", $orders)  
        ->where("financial_id", $request->financial_account_id)
        ->sum('amount'); 
        $total_cash = $financial_accounts - $expenses;
        if($total_cash < $request->amount){
            return response()->json([
                "errors" => "cash not enough"
            ], 400);
        }
        $this->expenses
        ->create($expenseRequest);
        //_____________________________________
        $financial = FinantiolAcounting::
        where("id", $request->financial_account_id)
        ->first();
        if($financial && $financial->balance < $request->amount){
            return response()->json([
                "errors" => "Balance not enough"
            ], 400);
        }
        if($financial){
            $financial->balance -= $request->amount;
            $financial->save();
        }

        return response()->json([
            "success" => "You add expense success"
        ]);
    }

    public function update(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'expense' => ['required'],
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

        $expenses = $this->expenses
        ->where("id", $id)
        ->where("cahier_man_id", $request->user()->id)
        ->first();
        $financial = FinantiolAcounting::
        where("id", $expenses->financial_account_id)
        ->first();
        if($financial){
            $financial->balance += $expenses->amount;
            $financial->save();
        }
        $expenses->expense = $request->expense;
        $expenses->financial_account_id = $request->financial_account_id;
        $expenses->category_id = $request->category_id;
        $expenses->amount = $request->amount;
        $expenses->note = $request->note;
        $expenses->save();

        $financial = FinantiolAcounting::
        where("id", $request->financial_account_id)
        ->first();
        if($financial){
            $financial->balance -= $request->amount;
            $financial->save();
        }

        return response()->json([
            "success" => "You update expense success"
        ]);
    }
}
