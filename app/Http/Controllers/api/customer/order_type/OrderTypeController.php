<?php

namespace App\Http\Controllers\api\customer\order_type;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Setting;

class OrderTypeController extends Controller
{
    public function __construct(private Setting $settings){}

    public function view(){
        // https://bcknd.food2go.online/customer/order_type
        $order_types = $this->settings
        ->where('name', 'order_type')
        ->first();
        if (empty($order_types)) {
            $order_types = $this->settings
            ->create([
                'name' => 'order_type',
                'setting' => json_encode([
                    [
                        'type' => 'take_away',
                        'status' => 1
                    ],
                    [
                        'type' => 'dine_in',
                        'status' => 1
                    ],
                    [
                        'type' => 'delivery',
                        'status' => 1
                    ]
                ]),
            ]);
        }
        $order_types = $order_types->setting;
        $order_types = json_decode($order_types);

        return response()->json([
            'order_types' => $order_types
        ]);
    }
}
