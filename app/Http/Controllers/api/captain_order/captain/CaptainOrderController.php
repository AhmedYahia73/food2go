<?php

namespace App\Http\Controllers\api\captain_order\captain;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CaptainOrderController extends Controller
{
    public function view(Request $request){
        return response()->json([
            "print_name" => $request->user()->print_name,
            "print_port" => $request->user()->print_port,
            "print_ip" => $request->user()->print_ip,
            "print_type" => $request->user()->print_type,
        ]);
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'print_type' => ['in:usb,network'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $request->user()->print_name = $request->print_name ?? $request->user()->print_name;
        $request->user()->print_port = $request->print_port ?? $request->user()->print_port;
        $request->user()->print_ip = $request->print_ip ?? $request->user()->print_ip;
        $request->user()->print_type = $request->print_type ?? $request->user()->print_type;
        $request->user()->save();

        return response()->json([
            "success" => "you update data success", 
        ]);
    }
}
