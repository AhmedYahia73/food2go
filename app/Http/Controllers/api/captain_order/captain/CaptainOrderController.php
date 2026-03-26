<?php

namespace App\Http\Controllers\api\captain_order\captain;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\CaptainPrinter;

class CaptainOrderController extends Controller
{
    public function view(Request $request){
        $captain_printers = CaptainPrinter::
        where("captain_order_id", $request->user()->id)
        ->get();

        return response()->json([
            "captain_printers" => $captain_printers, 
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'print_type' => ["required", 'in:usb,network'],
            'print_name' => ["required"],
            'print_port' => ["required"],
            'print_ip' => ["required"],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
  
        CaptainPrinter::create([
            "print_name" => $request->print_name,
            "print_type" => $request->print_type,
            "print_port" => $request->print_port,
            "print_ip" => $request->print_ip,
            "captain_order_id" => auth()->user()->id
        ]); 

        return response()->json([
            "success" => "you add data success", 
        ]);
    }

    public function update(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'print_type' => ["required", 'in:usb,network'],
            'print_name' => ["required"],
            'print_port' => ["required"],
            'print_ip' => ["required"],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        CaptainPrinter::
        where("id", $id)
        ->update([
            "print_name" => $request->print_name,
            "print_type" => $request->print_type,
            "print_port" => $request->print_port,
            "print_ip" => $request->print_ip, 
        ]);

        return response()->json([
            "success" => "you update data success", 
        ]);
    }

    public function delete(Request $request, $id){
 

        CaptainPrinter::
        where("id", $id)
        ->delete();

        return response()->json([
            "success" => "you delete data success", 
        ]);
    }
}
