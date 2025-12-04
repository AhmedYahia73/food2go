<?php

namespace App\Http\Controllers\api\admin\supplier;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Supplier;

class SupplierController extends Controller
{
    public function __construct(private Supplier $suppliers){}

    public function view(Request $request){
        $suppliers = $this->suppliers
        ->select("id", "name", "phone", "email", "status", "balance")
        ->get();

        return response()->json([
            "suppliers" => $suppliers
        ]);
    }

    public function supplier(Request $request, $id){
        $supplier = $this->suppliers
        ->select("id", "name", "phone", "email", "status", "balance")
        ->where("id", $id)
        ->first();

        return response()->json([
            "supplier" => $supplier
        ]);
    }

    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'phone' => ['required', 'unique:suppliers,phone'],
            'email' => ['required', 'unique:suppliers,email'],
            'status' => ['required'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
    }

    public function modify(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'name' => ['required'],
            'phone' => ['required', 'unique:suppliers,phone,' . $id],
            'email' => ['required', 'unique:suppliers,email,' . $id],
            'status' => ['required'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
    }

    public function delete(Request $request, $id){
        
    }
}
