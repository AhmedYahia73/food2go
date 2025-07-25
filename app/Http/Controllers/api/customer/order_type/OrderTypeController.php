<?php

namespace App\Http\Controllers\api\customer\order_type;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Setting;
use App\Models\PaymentMethod;
use App\Models\Branch;
use App\Models\CompanyInfo;

class OrderTypeController extends Controller
{
    public function __construct(private Setting $settings, private PaymentMethod $payment_methods,
    private Branch $branches, private CompanyInfo $company_info){}

    public function view(Request $request){
        // https://bcknd.food2go.online/customer/order_type
        $order_types = $this->settings
        ->where('name', 'order_type')
        ->first();
        $call_center_phone = $this->company_info
        ->orderByDesc('id')
        ->first()?->phone;
        $branches = $this->branches
        ->get()
        ->map(function($item) use($request){
            $item->name = $request->locale == 'ar' ? $item?->translations
            ?->where('locale', 'ar')?->first()?->value ?? $item->name : $item->name;
            return $item;
        });
        if (empty($order_types)) {
            $order_types = $this->settings
            ->create([
                'name' => 'order_type',
                'setting' => json_encode([
                    [
                        'id' => 1,
                        'type' => 'take_away',
                        'status' => 1
                    ],
                    [
                        'id' => 2,
                        'type' => 'dine_in',
                        'status' => 1
                    ],
                    [
                        'id' => 3,
                        'type' => 'delivery',
                        'status' => 1
                    ]
                ]),
            ]);
        }
        $order_types = $order_types->setting;
        $order_types = json_decode($order_types);
        $order_types = collect($order_types)
        ->where('status', 1)
        ->values();
        $payment_methods = $this->payment_methods
        ->where('status', 1)
        ->get();

        return response()->json([
            'order_types' => $order_types,
            'payment_methods' => $payment_methods,
            'branches' => $branches,
            'call_center_phone' => $call_center_phone,
        ]);
    }
}
