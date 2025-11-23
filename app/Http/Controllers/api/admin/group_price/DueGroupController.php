<?php

namespace App\Http\Controllers\api\admin\group_price;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\GroupProduct;
use App\Models\ModulePayment;
use App\Models\ModuleFinancial;
use App\Models\FinantiolAcounting;

class DueGroupController extends Controller
{
    public function __construct(private GroupProduct $group_products,
    private ModulePayment $module_payment, private FinantiolAcounting $financial_account,
    private ModuleFinancial $module_financial){}

    public function view(Request $request, $id){
        $due = $this->group_products
        ->where("id", $id)
        ->first();
        if (empty($due)) {
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }

        $due_amount = $due->balance;
        $module_payment = $this->module_payment
        ->where("group_product_id", $id)
        ->orderByDesc("id")
        ->get()
        ->map(function($item){
            $module_financials = $item->module_financials
            ->map(function($element){
                return [
                    "amount" => $element->amount,
                    "financial_accounting" => $element?->financial?->name,
                ];
            });
            return [
                "id" => $item->id,
                "total" => $item->amount,
                "financials" => $module_financials,
            ];
        });

        $financial_account = $this->financial_account
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
            ];
        });

        return response()->json([
            "due_amount" => $due_amount,
            "module_payment" => $module_payment,
            "financial_account" => $financial_account,
        ]);
    }

    public function payment(Request $request){
        $validator = Validator::make($request->all(), [
            'group_product_id' => ['required', 'exists:group_products,id'], 
            'financials' => ['required', 'array'], 
            'financials.*.id' => ['required', 'exists:finantiol_acountings,id'], 
            'financials.*.amount' => ['required', 'numeric'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $data = $request->financials;
        $total = array_sum(array_column($data, 'amount'));
        $group_products = $this->group_products
        ->where("id", $request->group_product_id)
        ->first();
        if($group_products->balance < $total){
            return response()->json([
                "errors" => "Total Payment > Due"
            ], 400);
        }
        $module_payment = $this->module_payment
        ->create([
            "group_product_id" => $request->group_product_id,
            "amount" => $total
        ]);
        foreach ($request->financials as $item) {
            $this->module_financial
            ->create([
                "module_id" => $module_payment->id,
                "financial_id" => $item['id'],
                "amount" => $item['amount'],
            ]);
            $financial_account = $this->financial_account
            ->where("id", $item['id'])
            ->first();
            $financial_account->increment("balance", $item['amount']);
        }
        $group_products->decrement("balance", $total);

        return response()->json([
            "success" => "You payment success"
        ]);
    }
}
