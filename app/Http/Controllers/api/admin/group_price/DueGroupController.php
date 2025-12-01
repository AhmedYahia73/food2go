<?php

namespace App\Http\Controllers\api\admin\group_price;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\GroupProduct;
use App\Models\ModulePayment;
use App\Models\ModuleFinancial;
use App\Models\FinantiolAcounting;
use App\Models\Order; 
use App\Models\TimeSittings; 

class DueGroupController extends Controller
{
    public function __construct(private GroupProduct $group_products,
    private ModulePayment $module_payment, private FinantiolAcounting $financial_account,
    private ModuleFinancial $module_financial, private TimeSittings $TimeSittings,
    private Order $orders){}

    public function view(Request $request, $id){
        
        $time_sittings = $this->TimeSittings 
        ->get();
        if ($time_sittings->count() > 0) {
            $from = $time_sittings[0]->from;
            $end = date('Y-m-d') . ' ' . $time_sittings[$time_sittings->count() - 1]->from;
            $hours = $time_sittings[$time_sittings->count() - 1]->hours;
            $minutes = $time_sittings[$time_sittings->count() - 1]->minutes;
            $from = date('Y-m-d') . ' ' . $from;
            $start = Carbon::parse($from);
            $end = Carbon::parse($end);
			$end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
            if ($start >= $end) {
                $end = $end->addDay();
            }
			if($start >= now()){
                $start = $start->subDay();
			} 
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        }  

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
        ->where("created_at", ">=", $start)
        ->where("created_at", "<=", $end)
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
        $all_orders_due = $this->orders
        ->where('module_id', $id)
        ->sum("due_module");
        $due_orders_due = $this->orders
        ->where('module_id', $id)
        ->where("created_at", ">=", $start)
        ->where("created_at", "<=", $end)
        ->sum("due_module");
        
        $all_collect = $this->module_payment
        ->where("group_product_id", $id)
        ->where("created_at", ">=", $start)
        ->where("created_at", "<=", $end)
        ->sum("amount");

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
            'cashier_id' => ['required', 'exists:cashiers,id'],
            'cahier_man_id' => ['required', 'exists:cashier_men,id'],
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
                "cashier_id" => $request->cashier_id,
                "cahier_man_id" => $request->cahier_man_id,
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
