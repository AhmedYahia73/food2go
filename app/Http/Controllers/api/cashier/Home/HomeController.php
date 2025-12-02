<?php

namespace App\Http\Controllers\api\cashier\Home;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Cashier;
use App\Models\Order;
use App\Models\Setting;

use Carbon\Carbon;

class HomeController extends Controller
{
    public function __construct(private Cashier $cashier,
    private Order $order, private Setting $settings){}

    public function view(Request $request){
        // https://bcknd.food2go.online/cashier/home
        $locale = $request->locale ?? 'en';
        $cashiers = $this->cashier
        ->where('branch_id', $request->user()->branch_id)
        ->where('cashier_active', 0)
        ->where('status', 1)
        ->with("translations")
        ->get()
        ->map(function($item) use($locale){
            return [
                "id" => $item->id,
                "name" => $item->translations->where("locale", $locale)
                ->where("key", $item->name)
                ->first()?->value ?? $item->name,
                "branch_id" => $item->branch_id,
                "cashier_active" => $item->cashier_active,
                "created_at" => $item->created_at,
                "status" => $item->status,
                "updated_at" => $item->updated_at,
            ];
        });
 
  
        $hidden_cashiers = $this->cashier
        ->where('branch_id', $request->user()->branch_id)
        ->where('cashier_active', 1)
        ->where('status', 1)
        ->get()
        ->map(function($item) use($locale){
            return [
                "id" => $item->id,
                "name" => $item->translations->where("locale", $locale)
                ->where("key", $item->name)
                ->first()?->value ?? $item->name,
                "branch_id" => $item->branch_id,
                "cashier_active" => $item->cashier_active,
                "created_at" => $item->created_at,
                "status" => $item->status,
                "updated_at" => $item->updated_at,
            ];
        });

        return response()->json([
            'cashiers' => $cashiers,
            'hidden_cashiers' => $hidden_cashiers,
        ]);
    }

    // public function notification_sound(Request $request){
    //     $notification_sound = $this->settings
    //     ->
    // }

    public function active_cashier(Request $request, $id){
        // https://bcknd.food2go.online/cashier/home/active_cashier/{id}
        $this->cashier
        ->where('id', $id)
        ->update([
            'cashier_active' => 1
        ]);

        return response()->json([
            'success' => 'You activate cashier success'
        ]);
    }

    public function cashier_data(Request $request){
        
        $delivery_time = $this->settings
        ->where("name", "delivery_time")
        ->first()
        ->setting ?? "00:00:00";
        $orders = $this->order
        ->where('pos', 1)
        ->where('order_active', 1)
        ->with("branch:id,name,food_preparion_time")
        ->where('cashier_man_id', $request->user()->id)
        ->orderByDesc("id")
        ->get()
        ->map(function($item) use($delivery_time){
            $order_status = null;
            if($item->order_type == "take_away"){
                $food_preparion_time = "00:" . $item?->branch?->food_preparion_time ?? "00:00";
                $order_status = $item->take_away_status;
            }
            elseif($item->order_type == "delivery"){
                $time1 = Carbon::parse($item?->branch?->food_preparion_time ?? "00:00");
                $time2 = Carbon::parse($delivery_time);
                $totalSeconds = $time1->secondsSinceMidnight() + $time2->secondsSinceMidnight();
                $result = gmdate('H:i:s', $totalSeconds);

                $food_preparion_time = $result;
                $order_status = $item->delivery_status;
            }
            elseif ($item->order_type == "dine_in") {
                $food_preparion_time = "00:" . $item?->branch?->food_preparion_time ?? "00:00";
            }
            return [
                "id" => $item->id,
                "order_number" => $item->order_number,
                'food_preparion_time' => $food_preparion_time,
                "created_at" => $item->created_at,
                "order_details" => $item->order_details,  
                "order_type" => $item->order_type, 
                "order_status" => $order_status,  
            ];
        });
        $take_away = $orders->where('order_type', 'take_away')
        ->where('take_away_status', '!=', 'pick_up')
        ->values();
        $dine_in = $orders->where('order_type', 'dine_in')
        ->values();
        $delivery = $orders->where('order_type', 'delivery')
        ->values();
        // if($orders->order_type == 'take_away'){
        //     $orders->take_away_status = 'preparing';
        // }
        // $order->save();

        return response()->json([
            'take_away' => $take_away,
            'dine_in' => $dine_in,
            'delivery' => $delivery,
            'take_away_count' => $take_away->count(),
            'dine_in_count' => $dine_in->count(),
            'delivery_count' => $delivery->count(),
        ]);
    }
}
