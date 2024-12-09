<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Setting;

class OrderTypeController extends Controller
{
    public function __construct(private Setting $settings){}

    public function view(){
        // https://bcknd.food2go.online/admin/settings/order_type
        $order_types = $this->settings
        ->where('name', 'order_type')
        ->first();
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

        return response()->json([
            'order_types' => $order_types
        ]);
    }

    public function modify(Request $request){
        // https://bcknd.food2go.online/admin/settings/order_type/update
        // Keys 
        // id, status
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $order_types = $this->settings
        ->where('name', 'order_type')
        ->first();
        if (empty($order_types)) {
            return response()->json([
                'faild' => 'Order type does not found'
            ], 400);
        }
        else {
            $order_types = $this->settings
            ->where('name', 'order_type')
            ->first();
            $setting = $order_types->setting;
            $setting = json_decode($setting);
            $setting = collect($setting);
            $setting->where('id', $request->id)
            ->first()
            ->status =  $request->status;
            $order_types->update([
                'setting' => json_encode($setting),
            ]);
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }
}
