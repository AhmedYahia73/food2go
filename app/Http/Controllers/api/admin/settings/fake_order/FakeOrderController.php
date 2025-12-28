<?php

namespace App\Http\Controllers\api\admin\settings\fake_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Setting;

class FakeOrderController extends Controller
{
    public function view(Request $request){ 
        $fake_order_precentage = Setting::
        where("name", "fake_order_precentage")
        ->first()?->setting ?? null;
        $fake_order_limit = Setting::
        where("name", "fake_order_limit")
        ->first()?->setting ?? null;
        $fake_order_status = Setting::
        where("name", "fake_order_status")
        ->first()?->setting ?? null;
        $data = [
            "fake_order_precentage" => $fake_order_precentage,
            "fake_order_limit" => $fake_order_limit,
            "fake_order_password" => null,
            "fake_order_status" => $fake_order_status,
        ];

        return response()->json($data);
    }
    
    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'fake_order_precentage' => 'required|numeric',
            'fake_order_limit' => 'required|numeric', 
            'fake_order_status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $fake_order_precentage = Setting::
        where("name", "fake_order_precentage")
        ->first();
        $fake_order_limit = Setting::
        where("name", "fake_order_limit")
        ->first();
        $fake_order_status = Setting::
        where("name", "fake_order_status")
        ->first();
        if(empty($fake_order_precentage)){
            $validator = Validator::make($request->all(), [
                'fake_order_password' => 'required', 
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'errors' => $validator->errors(),
                ],400);
            }
        }
        if(!empty($request->fake_order_password)){ 
            $fake_order_password = Setting::
            where("name", "fake_order_password")
            ->first();
            if(empty($fake_order_password)){
                Setting::create([
                    'name' => "fake_order_password",
                    'setting' => $request->fake_order_password,
                ]);
            }
            else{
                $fake_order_password->setting = $request->fake_order_password;
                $fake_order_password->save();
            }
        }
        
        if(empty($fake_order_precentage)){
            Setting::create([
                'name' => "fake_order_precentage",
                'setting' => $request->fake_order_precentage,
            ]);
        }
        else{
            $fake_order_precentage->setting = $request->fake_order_precentage;
            $fake_order_precentage->save();
        }
        
        if(empty($fake_order_limit)){
            Setting::create([
                'name' => "fake_order_limit",
                'setting' => $request->fake_order_limit,
            ]);
        }
        else{
            $fake_order_limit->setting = $request->fake_order_limit;
            $fake_order_limit->save();
        }
        
        if(empty($fake_order_status)){
            Setting::create([
                'name' => "fake_order_status",
                'setting' => $request->fake_order_status,
            ]);
        }
        else{
            $fake_order_status->setting = $request->fake_order_status;
            $fake_order_status->save();
        }

        return response()->json([
            "success" => "You update data success"
        ]);
    }
    
    public function status(Request $request){
        $validator = Validator::make($request->all(), [ 
            'fake_order_status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $fake_order_password = Setting::
        where("name", "fake_order_password")
        ->first()?->setting ?? null;
        $fake_order_precentage = Setting::
        where("name", "fake_order_precentage")
        ->first()?->setting ?? null;
        $fake_order_limit = Setting::
        where("name", "fake_order_limit")
        ->first()?->setting ?? null;
        $fake_order_status = Setting::
        where("name", "fake_order_status")
        ->first()?->setting ?? null;
        if(empty($fake_order_password)){
            Setting::create([
                'name' => "fake_order_password",
                'setting' => $request->fake_order_password,
            ]);
        }
        else{
            $fake_order_password->setting = $request->fake_order_password;
            $fake_order_password->save();
        }
        
        if(empty($fake_order_precentage)){
            Setting::create([
                'name' => "fake_order_precentage",
                'setting' => $request->fake_order_precentage,
            ]);
        }
        else{
            $fake_order_precentage->setting = $request->fake_order_precentage;
            $fake_order_precentage->save();
        }
        
        if(empty($fake_order_limit)){
            Setting::create([
                'name' => "fake_order_limit",
                'setting' => $request->fake_order_limit,
            ]);
        }
        else{
            $fake_order_limit->setting = $request->fake_order_limit;
            $fake_order_limit->save();
        }
        
        if(empty($fake_order_status)){
            Setting::create([
                'name' => "fake_order_status",
                'setting' => $request->fake_order_status,
            ]);
        }
        else{
            $fake_order_status->setting = $request->fake_order_status;
            $fake_order_status->save();
        }

        return response()->json([
            "success" => "You update data success"
        ]);
    }
}
