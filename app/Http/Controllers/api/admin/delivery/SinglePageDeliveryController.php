<?php

namespace App\Http\Controllers\api\admin\delivery;

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
use App\Models\FinantiolAcounting;

class SinglePageDeliveryController extends Controller
{
    public function __construct(private Order $ordersModel,
    private OrderFinancial $order_financials, private TimeSittings $TimeSittings,
    private Cashier $cashiers, private CashierMan $cashier_men, 
    private FinantiolAcounting $financial_accounting, private Branch $branches){}
    
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

        return response()->json([
            "branches" => $branches,
            "cashiers" => $cashiers,
            "cashier_men" => $cashier_men,
            "financial_accounting" => $financial_accounting,
        ]);
    }
    
    public function orders(Request $request){
        $orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->whereNull("delivery_id")
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount, 
                "order_number" => $item->order_number,
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
    
    public function current_orders(Request $request, $id){
        $orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->where("order_status", "out_for_delivery")
            ->orWhere("delivery_status", "out_for_delivery");
        })
        ->where("delivery_id", $id)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount, 
                "order_number" => $item->order_number,
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

    public function delivered_order(Request $request, $id){
        $orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->where("order_status", "delivered")
            ->orWhere("delivery_status", "delivered");
        })
        ->where("delivery_id", $id)
        ->where("due_from_delivery", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount, 
                "order_number" => $item->order_number,
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
        $total_orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->where("order_status", "delivered")
            ->orWhere("delivery_status", "delivered");
        })
        ->where("due_from_delivery", 1)
        ->where("delivery_id", $id)
        ->sum('amount');
        $expected_orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->where("order_status", "out_for_delivery")
            ->orWhere("delivery_status", "out_for_delivery");
        })
        ->where("due_from_delivery", 1)
        ->where("delivery_id", $id)
        ->sum('amount');

        return response()->json([
            "due_orders" => $orders,
            "total_orders" => $total_orders,
            "expected_orders" => $expected_orders,
        ]);
    }

    public function filter_delivered_order(Request $request, $id){ 
        $validator = Validator::make($request->all(), [
            'from' => 'date|required',
            'to' => 'date|required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
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
            $start = Carbon::parse(date('Y-m-d') . ' ' . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' ' . ' 23:59:59');
        } 
        $start = Carbon::parse($request->from . ' ' . $start->format('H:i:s'));
        $end = Carbon::parse($request->to . ' ' . $end->format('H:i:s'));

        $orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->where("order_status", "delivered")
            ->orWhere("delivery_status", "delivered");
        })
        ->whereBetween('created_at', [$start, $end])
        ->where("delivery_id", $id)
        ->where("due_from_delivery", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount, 
                "order_number" => $item->order_number,
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
        $total_orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->where("order_status", "delivered")
            ->orWhere("delivery_status", "delivered");
        })
        ->whereBetween('created_at', [$start, $end])
        ->where("due_from_delivery", 1)
        ->where("delivery_id", $id)
        ->sum('amount');
        $expected_orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->where("order_status", "out_for_delivery")
            ->orWhere("delivery_status", "out_for_delivery");
        })
        ->whereBetween('created_at', [$start, $end])
        ->where("due_from_delivery", 1)
        ->where("delivery_id", $id)
        ->sum('amount');

        return response()->json([
            "due_orders" => $orders,
            "total_orders" => $total_orders,
            "expected_orders" => $expected_orders,
        ]);
    }

    public function faild_orders(Request $request, $id){
        
        $orders = $this->ordersModel
        ->where("order_type", "delivery")
        ->where(function($query){
            $query->whereIn("order_status", ['returned','faild_to_deliver','canceled','','refund'])
            ->orWhere("delivery_status", "returned");
        })
        ->where("delivery_id", $id)
        ->where("due_from_delivery", 1)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "amount" => $item->amount, 
                "order_number" => $item->order_number,
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
        }

        return response()->json([
            "success" => "You payment orders success"
        ]);
    }
}
