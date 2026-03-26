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

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'printers' => ['required', "array"],
            'printers.*.print_type' => ["required", 'in:usb,network'],
            'printers.*.print_name' => ["required"],
            'printers.*.print_port' => ["required"],
            'printers.*.print_ip' => ["required"],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        CaptainPrinter::
        where("captain_order_id", $request->user()->id)
        ->delete();
        foreach ($request->printers as $item) {
            CaptainPrinter::create([
                "print_name" => $item['print_name'],
                "print_type" => $item['print_type'],
                "print_port" => $item['print_port'],
                "print_ip" => $item['print_ip'],
                "captain_order_id" => auth()->user()->id
            ]);
        }

        return response()->json([
            "success" => "you update data success", 
        ]);
    }
}
