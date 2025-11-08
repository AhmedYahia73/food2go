<?php

namespace App\Http\Controllers\api\admin\expenses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\ExpenseList;
use App\Models\ExpenseCategory;
use App\Models\Translation;
use App\Models\TranslationTbl;

class ExpenseListController extends Controller
{
    public function __construct(private ExpenseList $expense,
    private ExpenseCategory $categories, private Translation $translations, 
    private TranslationTbl $translation_tbl){}

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
        $translations = $this->translations
        ->where('status', 1)
        ->get();
        $expense_names = [];
        foreach ($translations as $item) {
             $expense_name = $this->translation_tbl
             ->where('locale', $item->name)
             ->where('key', $expense->name)
             ->first();
            $expense_names[] = [
                'tranlation_id' => $item->id,
                'tranlation_name' => $item->name,
                'expense_name' => $expense_name->value ?? null,
            ]; 
        }

        return response()->json([
            "expense" => $expense, 
            'expense_names' => $expense_names,
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
            'names' => ['required', 'array'],
            'names.*.name' => ['required'],
            'names.*.tranlation_name' => ['required'],
            'names.*.tranlation_id' => ['required'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $names = $request->names;
        $default = $names[0]['name'];
        $expenseRequest = $validator->validated();
        $expenseRequest['name'] = $default;
        $expense = $this->expense
        ->create($expenseRequest);

        foreach ($names as $item) {
            if (!empty($item['name'])) {
                $expense->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default,
                    'value' => $item['name']
                ]); 
            }
        } 

        return response()->json([
            "success" => "You add expense success"
        ]);
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
           'category_id' => ['required', 'exists:expense_categories,id'],
            'names' => ['required', 'array'],
            'names.*.name' => ['required'],
            'names.*.tranlation_name' => ['required'],
            'names.*.tranlation_id' => ['required'],
            'status' => ['required', 'boolean'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $names = $request->names;
        $default = $names[0]['name'];
        $expenseRequest = $validator->validated();
        $expenseRequest['name'] = $default;
        $expense = $this->expense
        ->where("id", $id)
        ->first();
        if(!$expense){
            return response()->json([
                "errors" => "id is wrong"
            ]);
        }
        $expense->update($expenseRequest);

        $expense->translations()->delete();
        foreach ($names as $item) {
            if (!empty($item['name'])) {
                $expense->translations()->create([
                    'locale' => $item['tranlation_name'],
                    'key' => $default,
                    'value' => $item['name']
                ]); 
            }
        } 

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
