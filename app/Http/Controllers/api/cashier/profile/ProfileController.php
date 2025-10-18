<?php

namespace App\Http\Controllers\api\cashier\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\CashierMan;

class ProfileController extends Controller
{
    public function __construct(private CashierMan $cashier_man){}
    use image;

    public function view(Request $request){
        return response()->json([
            "user_name" => $request->user()->user_name,
            "status" => $request->user()->status,
            "image" => $request->user()->image_link,
        ]);
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'user_name' => 'sometimes',
            'password' => 'sometimes',
            'status' => 'sometimes|boolean',
            'image' => 'sometimes',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $user = $request->user();
        $user->user_name = $request->user_name ?? $user->user_name;
        $user->password = $request->password ? bcrypt($request->password) : $user->password;
        $user->status = $request->status ?? $user->status;
        if($request->image){
            $imag_path = $this->upload($request, 'image', 'cashier/profile_image');
            $user->image = $imag_path;
        }
        $user->save();

        return response()->json([
            'success' => "You update profile success"
        ]);
    }
}
