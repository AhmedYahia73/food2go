<?php

namespace App\Http\Controllers\api\cashier\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\GeneratedDiscountCode;

class DiscountController extends Controller
{
    public function __construct(private GeneratedDiscountCode $discount_code){}

    public function check_discount_code(Request $request){
        $validator = Validator::make($request->all(), [
            'code' => ['required'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $discount_code = $this->discount_code
        ->where("code", $request->code)
        ->with("group")
        ->first();
        if(empty($discount_code)){
            return response()->json([
                "errors" => "code is wong"
            ], 400);
        }
        if(empty($discount_code->group)){
            return response()->json([
                "errors" => "code is expired"
            ], 400);
        }
        if($discount_code->usage >= $discount_code->group->usage_number ||
           $discount_code->group->start > now() ||$discount_code->group->end < now()){
            return response()->json([
                "errors" => "code is expired"
            ], 400);
        }
        $discount_code->usage += 1;
        $discount_code->save();

        return response()->json([
            "success" => true,
            "discount" => $discount_code->group->discount,
        ]);
    }
}
