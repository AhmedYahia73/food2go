<?php

namespace App\Http\Controllers\api\captain_order\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\trait\image;

class ProfileController extends Controller
{
    use image;

    public function view(Request $request){
        return response()->json([
            "name" => $request->user()->name,
            "user_name" => $request->user()->user_name,
            "phone" => $request->user()->phone,
            "image" => $request->user()->image_link,
        ]);
    }

    public function update_profile(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes',
            'user_name' => 'sometimes',
            'phone' => "sometimes|unique:captain_orders,phone," . $request->user()->id,
            'image' => 'sometimes',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $request->user()->name = $request->name ?? $request->user()->name;
        $request->user()->user_name = $request->user_name ?? $request->user()->user_name;
        $request->user()->phone = $request->phone ?? $request->user()->phone;
       if(!empty($request->password)){
            $request->user()->password = $request->password ?? $request->user()->password;
        }
        if(!empty($request->image)){
            $imag_path = $this->upload($request, 'image', 'captain_order/image');
            $request->user()->image = $imag_path ?? $request->user()->image;
        }
        $request->user()->save();

        return response()->json([
            "success" => "You update profile success"
        ]);
    }
}
