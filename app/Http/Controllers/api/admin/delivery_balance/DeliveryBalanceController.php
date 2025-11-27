<?php

namespace App\Http\Controllers\api\admin\delivery_balance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\OrderFinancial;
use App\Models\TimeSittings;

use App\Models\Branch;
use App\Models\Cashier;
use App\Models\CashierMan;
use App\Models\Delivery;
use App\Models\FinantiolAcounting;

class DeliveryBalanceController extends Controller
{
    public function __construct(private Order $ordersModel,
    private OrderFinancial $order_financials, private TimeSittings $TimeSittings,
    private Cashier $cashiers, private CashierMan $cashier_men, 
    private FinantiolAcounting $financial_accounting, private Branch $branches,
    private Delivery $deliveries){}
    
    public function lists(Request $request){ 
        $branches = $this->branches
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name, 
            ];
        });
        $cashiers = $this->cashiers
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "branch_id" => $item->branch_id,
            ];
        });
        $cashier_men = $this->cashier_men
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "user_name" => $item->user_name,
                "branch_id" => $item->branch_id,
            ];
        });
        $financial_accounting = $this->financial_accounting
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->name,
                "description_status" => $item->description_status,
            ];
        });
        $deliveries = $this->deliveries
        ->where("status", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "name" => $item->f_name . ' ' . $item->l_name,
                "phone" => $item->phone,
            ];
        });

        return response()->json([
            "branches" => $branches,
            "cashiers" => $cashiers,
            "cashier_men" => $cashier_men,
            "financial_accounting" => $financial_accounting,
            "deliveries" => $deliveries,
        ]);
    }
    
    public function orders(Request $request){
        $orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->whereNull("delivery_id")
        ->with(['branch', 'user', 'address', 'cashier_man'])
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount, 
                "order_number" => $item->order_number,
                "order_status" => $item->pos ? $item->delivery_status: $item->order_status,
                "source" => $item->source,
                "branch" =>  $item?->branch?->name,
                "user" => [
                    "name" => $item?->user?->name,
                    "phone" => $item?->user?->phone,
                ],
                "address" => $item?->address,
                "cashier_man" => $item?->cashier_man?->user_name,
                "date" => $item?->created_at?->format("Y-m-d"),
                "time" => $item?->created_at?->format("H:i:s"),
            ];
        });

        return response()->json([
            "orders" => $orders,
        ]);
    }
    
    public function current_orders(Request $request){
        $total_orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where("due_from_delivery", 1) 
        ->with(['branch', 'user', 'address', 'cashier_man'])
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount, 
                "order_number" => $item->order_number,
                "source" => $item->source,
                "order_status" => $item->pos ? $item->delivery_status: $item->order_status,
                "branch" =>  $item?->branch?->name,
                "user" => [
                    "name" => $item?->user?->name,
                    "phone" => $item?->user?->phone,
                ],
                "address" => $item?->address,
                "cashier_man" => $item?->cashier_man?->user_name,
                "date" => $item?->created_at?->format("Y-m-d"),
                "time" => $item?->created_at?->format("H:i:s"),
            ];
        }); 
        $total_amount = $this->ordersModel
        ->where("order_type", "delivery")
        ->where("due_from_delivery", 1)
        ->sum("amount");
        $on_the_way_amount = $this->ordersModel
        ->where("order_type", "delivery")
        ->where("due_from_delivery", 1)
        ->where(function($query){
            $query->where("order_status", "out_for_delivery")
            ->where("pos", 0)
            ->orWhere("delivery_status", "out_for_delivery")
            ->where("pos", 1);
        })
        ->sum("amount");
        $cash_on_hand_amount = $this->ordersModel
        ->where("order_type", "delivery")
        ->where("due_from_delivery", 1)
        ->where(function($query){
            $query->where("order_status", "delivered")
            ->where("pos", 0)
            ->orWhere("delivery_status", "delivered")
            ->where("pos", 1);
        })
        ->sum("amount");

        return response()->json([
            "total_orders" => $total_orders,
            "total_amount" => $total_amount, 
            "on_the_way_amount" => $on_the_way_amount,
            "cash_on_hand_amount" => $cash_on_hand_amount,
        ]);
    }

    public function filter_current_orders(Request $request){ 
        $validator = Validator::make($request->all(), [
            'delivery_id' => 'exists:deliveries,id|required', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        } 
        
        $total_orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where("due_from_delivery", 1) 
        ->where("delivery_id", $request->delivery_id)
        ->with(['branch', 'user', 'address', 'cashier_man']) 
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount, 
                "order_number" => $item->order_number,
                "source" => $item->source,
                "order_status" => $item->pos ? $item->delivery_status: $item->order_status,
                "branch" =>  $item?->branch?->name,
                "user" => [
                    "name" => $item?->user?->name,
                    "phone" => $item?->user?->phone,
                ],
                "address" => $item?->address,
                "cashier_man" => $item?->cashier_man?->user_name,
                "date" => $item?->created_at?->format("Y-m-d"),
                "time" => $item?->created_at?->format("H:i:s"),
            ];
        }); 
        $total_amount = $this->ordersModel
        ->where("order_type", "delivery")
        ->where("due_from_delivery", 1)
        ->where("delivery_id", $request->delivery_id)
        ->sum("amount");
        $on_the_way_amount = $this->ordersModel
        ->where("order_type", "delivery")
        ->where("due_from_delivery", 1)
        ->where(function($query){
            $query->where("order_status", "out_for_delivery")
            ->orWhere("delivery_status", "out_for_delivery");
        })
        ->where("delivery_id", $request->delivery_id)
        ->sum("amount");
        $cash_on_hand_amount = $this->ordersModel
        ->where("order_type", "delivery")
        ->where("due_from_delivery", 1)
        ->where("delivery_id", $request->delivery_id)
        ->where(function($query){
            $query->where("order_status", "delivered")
            ->orWhere("delivery_status", "delivered");
        })
        ->sum("amount"); 

        return response()->json([
            "total_orders" => $total_orders,
            "total_amount" => $total_amount,
            "on_the_way_amount" => $on_the_way_amount,
            "cash_on_hand_amount" => $cash_on_hand_amount,
        ]);
    }

    public function faild_orders(Request $request){
        
        $orders = $this->ordersModel
        ->with(['branch', 'user', 'address', 'cashier_man'])
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->whereIn("order_status", ['returned','faild_to_deliver','canceled','','refund'])
            ->orWhere("delivery_status", "returned");
        }) 
        ->where("due_from_delivery", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount, 
                "order_number" => $item->order_number,
                "order_status" => $item->pos ? $item->delivery_status: $item->order_status,
                "source" => $item->source,
                "branch" =>  $item?->branch?->name,
                "user" => [
                    "name" => $item?->user?->name,
                    "phone" => $item?->user?->phone,
                ],
                "address" => $item?->address,
                "cashier_man" => $item?->cashier_man?->user_name,
                "date" => $item?->created_at?->format("Y-m-d"),
                "time" => $item?->created_at?->format("H:i:s"),
            ];
        });

        return response()->json([
            "orders" => $orders
        ]);
    }

    public function confirm_faild_order(Request $request){ 
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|exists:orders,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->whereIn("order_status", ['returned','faild_to_deliver','canceled','','refund'])
            ->orWhere("delivery_status", "returned");
        })
        ->whereIn("id", $request->order_ids)
        ->where("due_from_delivery", 1)
        ->update([ 
            "due_from_delivery" => 0
        ]);

        return response()->json([
            "success" => "You update orders success"
        ]);
    }

    public function pay_orders(Request $request){
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|exists:orders,id',
            'financial_id' => 'required|exists:finantiol_acountings,id',
            'branch_id' => 'required|exists:branches,id',
            'cashier_id' => 'required|exists:cashiers,id',
            'cashier_man_id' => 'required|exists:cashier_men,id',
            "description" => 'sometimes',
            "transition_id" => 'sometimes',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $orders = $this->ordersModel
        ->where("order_type", "delivery") 
        ->whereIn("id", $request->order_ids) 
        ->get();
        $financial_accounting = $this->financial_accounting
        ->where("id", $request->financial_id)
        ->first();
        $total= 0 ;
        foreach ($orders as $item) {
            $this->order_financials
            ->create([
                "order_id" => $item->id,
                "amount" => $item->amount,
                "financial_id" => $request->financial_id,
                "cashier_id" => $request->cashier_id,
                "cashier_man_id" => $request->cashier_man_id,
                "description" => $request->description ?? null,
                "transition_id" => $request->transition_id ?? null,
            ]);
            $item->update([
                "due_from_delivery" => 0
            ]);
            $total += $item->amount;
        }
        $financial_accounting->increment('balance', $total); 

        return response()->json([
            "success" => "You payment orders success"
        ]);
    }

    public function orders_delivery(Request $request){
        $validator = Validator::make($request->all(), [
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|exists:orders,id',
            'delivery_id' => 'required|exists:deliveries,id', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $orders = $this->ordersModel
        ->where("order_type", "delivery") 
        ->whereIn("id", $request->order_ids) 
        ->update([
            "delivery_id" => $request->delivery_id,
            "order_status" => 'out_for_delivery',
            "delivery_status" => 'out_for_delivery',
        ]);

        return response()->json([
            "success" => "You select delivery success"
        ]);
    }

    public function order_history(Request $request){ 
        $orders = $this->ordersModel
        ->with(['branch', 'user', 'address', 'cashier_man'])
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->where("order_status", 'delivered')
            ->orWhere("delivery_status", "delivered");
        }) 
        ->where("due_from_delivery", 0)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount, 
                "order_number" => $item->order_number,
                "order_status" => $item->pos ? $item->delivery_status: $item->order_status,
                "source" => $item->source,
                "delivery_id" => $item->delivery_id ,
                "branch" =>  $item?->branch?->name,
                "user" => [
                    "name" => $item?->user?->name,
                    "phone" => $item?->user?->phone,
                ],
                "address" => $item?->address,
                "cashier_man" => $item?->cashier_man?->user_name,
                "date" => $item?->created_at?->format("Y-m-d"),
                "time" => $item?->created_at?->format("H:i:s"),
            ];
        });

        return response()->json([
            "orders" => $orders
        ]);
    }
}
