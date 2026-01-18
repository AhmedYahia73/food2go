<?php

namespace App\Http\Controllers\api\cashier\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\CashierMan;
use App\Models\Cashier;

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
        if($request->image){
            $imag_path = $this->upload($request, 'image', 'cashier/profile_image');
            $user->image = $imag_path;
        }
        $user->save();

        return response()->json([
            'success' => "You update profile success"
        ]);
    }

    public function printer(Request $request){
        return response()->json([
            "print_name" => $request->user()?->cashier?->print_name,
            "print_type" => $request->user()?->cashier?->print_type,
            "print_port" => $request->user()?->cashier?->print_port,
            "print_ip" => $request->user()?->cashier?->print_ip,
        ]);
    }

    public function printer_update(Request $request){
        $validator = Validator::make($request->all(), [
            'print_name' => 'sometimes',
            'print_type' => 'sometimes|in:usb,network', 
            'print_port' => 'sometimes',
            'print_ip' => 'sometimes',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $cashier = Cashier::
        where("id", $request->user()->cashier_id)
        ->first();
        if(empty($cashier)){
            return response()->json([
                "errors" => "Cashier is empty"
            ], 400);
        }
        $cashier->update([
            'print_name' => $request->print_name ?? $cashier->print_name,
            'print_type' => $request->print_type ?? $cashier->print_type, 
            'print_port' => $request->print_port ?? $cashier->print_port,
            'print_ip' => $request->print_ip ?? $cashier->print_ip,
        ]);

        return response()->json([
            'success' => "You update printer success"
        ]);
    }
}
