<?php

namespace App\Http\Controllers\api\admin\pos\kitchen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\PrinterKitchen;
use App\Models\GroupProduct;

class KitchenPrinterController extends Controller
{ 
    public function index($id)
    {
        $printer_kitchen = PrinterKitchen::
        with("group_product:id,name")
        ->where("kitchen_id", $id)
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "print_name" => $item->print_name,
                "print_ip" => $item->print_ip,
                "print_status" => $item->print_status,
                "print_type" => $item->print_type,
                "print_port" => $item->print_port,
                "module" => $item->module,
                "group_product" => $item->group_product,
            ];
        });

        return response()->json([
            "printer_kitchen" => $printer_kitchen
        ]);
    }

    public function lists(){
        $group_products = GroupProduct::
        select("id", "name")
        ->get();
        $modules = [
            "take_away",
            "dine_in",
            "delivery",
        ];

        return response()->json([
            "group_products" => $group_products,
            "modules" => $modules,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'print_name' => 'required',
            'print_ip' => 'required',
            'print_status' => 'required|boolean',
            'print_type' => 'required|in:usb,network',
            'print_port' => 'required',
            'kitchen_id' => 'required|exists:kitchens,id',
            "module" => "array",
            "module.*" => "required|in:take_away,dine_in,delivery",
            "group_modules" => "array",
            "group_modules.*" => "required|exists:group_products,id",
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'errors' => $validator->errors(),
            ],400);
        }
        $printer = PrinterKitchen::create([ 
            'print_name' => $request->print_name ?? null,
            'print_ip' => $request->print_ip ?? null,
            'print_status' => $request->print_status ?? null,
            'print_type' => $request->print_type ?? null,
            'print_port' => $request->print_port ?? null, 
            'kitchen_id' => $request->kitchen_id ?? null,
            'module' => $request->module ?? null,
        ]);
        $printer->group_product()->attach($request->group_modules);

        return response()->json([
            "success" => "You add data success"
        ]);
    }

    public function show(string $id)
    {
        $printer_kitchen = PrinterKitchen::
        with("group_product:id,name")
        ->where("id", $id)
        ->first();

        return response()->json([
            "printer_kitchen" => $printer_kitchen
        ]);
    }

    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'print_name' => 'required',
            'print_ip' => 'required',
            'print_status' => 'required|boolean',
            'print_type' => 'required|in:usb,network',
            'print_port' => 'required',
            'kitchen_id' => 'required|exists:kitchens,id',
            "module" => "array",
            "module.*" => "required|in:take_away,dine_in,delivery",
            "group_modules" => "array",
            "group_modules.*" => "required|exists:group_products,id",
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                    'errors' => $validator->errors(),
            ],400);
        }
        $printer = PrinterKitchen::
        where("id", $id)
        ->first();
        if(!$printer){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        $printer
        ->update([ 
            'print_name' => $request->print_name ?? null,
            'print_ip' => $request->print_ip ?? null,
            'print_status' => $request->print_status ?? null,
            'print_type' => $request->print_type ?? null,
            'print_port' => $request->print_port ?? null, 
            'kitchen_id' => $request->kitchen_id ?? null,
            'module' => $request->module ?? null,
        ]);
        $printer->group_product()->sync($request->group_modules);

        return response()->json([
            "success" => "You update data success"
        ]);
    }

    public function destroy(string $id)
    {
        $printer = PrinterKitchen::
        where("id", $id)
        ->first();
        if(!$printer){
            return response()->json([
                "errors" => "id is wrong"
            ], 400);
        }
        $printer->delete();
        
        return response()->json([
            "success" => "You delete data success"
        ]);
    }
}
