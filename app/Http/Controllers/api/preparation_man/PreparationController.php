<?php

namespace App\Http\Controllers\api\preparation_man;

use App\Http\Controllers\Controller;
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
        ->get();
        $
        $order_preparation_format;
    }
    
    public function preparation_status(Request $request, $id){

    }
    
    public function notification(Request $request){

    }
    
    public function read_status(Request $request){

    }
}
