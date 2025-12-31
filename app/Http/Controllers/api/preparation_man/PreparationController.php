<?php

namespace App\Http\Controllers\api\preparation_man;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Http\Request;
use App\trait\OrderFormat;

use App\Models\Order;
use App\Models\Setting;

class PreparationController extends Controller
{
    public function __construct(private Order $orders,
    private Setting $settings){}
    use OrderFormat;
    
    public function preparation_orders(Request $request){
        $locale = $request->locale ?? "en";
        $orders_query = $this->orders
        ->where("order_type", "!=", "dine_in")
        ->where(function($query){
            $query->where("take_away_status", "!=", "pick_up")
            ->where("order_type", "take_away")
            ->orWhereIn("delivery_status", ['watting', 'preparing', 'done'])
            ->where("order_type", "delivery");
        })
        ->get();
        $orders = [];
        foreach ($orders_query as $order) {
            $orders[] = $this->order_preparation_format($order, $locale);
        }

        return response()->json([
            "orders" => $orders,
        ]);
    }
    
    public function preparation_status(Request $request, $id){
        $order = $this->orders
        ->where("id", $id)
        ->first();
        if (empty($order)) {
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }

        if ($order->order_type == "take_away") {
            $order->take_away_status = "preparation";
        }
        elseif ($order->order_type == "delivery") {
            $order->delivery_status = "preparation";
        }
        $order->save();

        return response()->json([
            "success" => "you update status success"
        ]);
    }
    
    public function notification(Request $request){
        $locale = $this->settings
        ->where("name", "setting_lang")
        ->first()?->setting ?? 'en';
        $orders_query = $this->orders
        ->where("order_type", "!=", "dine_in")
        ->where(function($query){
            $query->where("take_away_status", "!=", "pick_up")
            ->where("order_type", "take_away")
            ->orWhereIn("delivery_status", ['watting', 'preparing', 'done'])
            ->where("order_type", "delivery");
        })
        ->where("preparation_read_status", false)
        ->get();
        $orders = [];
        foreach ($orders_query as $order) {
            $orders[] = $this->order_preparation_format($order, $locale);
        }

        return response()->json([
            "orders" => $orders,
        ]);
    }
    
    public function read_status(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'preparation_read_status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $this->orders
        ->where("id", $id)
        ->update([
            "preparation_read_status" => $request->preparation_read_status
        ]);

        return response()->json([
            "success" => "You read order success",
        ]);
    }
}
