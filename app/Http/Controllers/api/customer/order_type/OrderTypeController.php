<?php

namespace App\Http\Controllers\api\customer\order_type;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Setting;
use App\Models\PaymentMethod;

class OrderTypeController extends Controller
{
    public function __construct(private Setting $settings, private PaymentMethod $payment_methods){}

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
        $payment_methods = $this->payment_methods
        ->where('status', 1)
        ->get();

        return response()->json([
            'order_types' => $order_types,
            'payment_methods' => $payment_methods,
        ]);
    }
}
