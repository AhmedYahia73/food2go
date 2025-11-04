<?php

namespace App\Http\Controllers\api\admin\order_precentage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Setting;

class OrderPrecentageController extends Controller
{
    public function __construct(private Setting $settings){}

    public function view(){
        $order_precentage = $this->settings
        ->where('name', 'order_precentage')
        ->first()?->setting ?? 100;

        return response()->json([
            'order_precentage' => $order_precentage
        ]);
    }

    public function create_update(Request $request){
        $validator = Validator::make($request->all(), [
            'order_precentage' => 'required|numeric',
            'password' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $order_precentage = $this->settings
        ->where('name', 'order_precentage')
        ->first();
        if(empty($order_precentage)){
            $this->settings
            ->create([
                'name' => 'order_precentage',
                'setting' => $request->order_precentage,
            ]);
        }
        else{
            $this->settings
            ->where('name', 'order_precentage')
            ->update([
                'setting' => $request->order_precentage,
            ]); 
        }
        $password = $this->settings
        ->where('name', 'password')
        ->first();
        if(empty($password)){
            $this->settings
            ->create([
                'name' => 'password',
                'setting' => $request->password,
            ]);
        }
        else{
            $this->settings
            ->where('name', 'password')
            ->update([
                'setting' => $request->password,
            ]); 
        }

        return response()->json([
            'success' => 'You update data success'
        ]);
    }
}
