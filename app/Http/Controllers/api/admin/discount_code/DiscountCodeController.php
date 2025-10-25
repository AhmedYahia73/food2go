<?php

namespace App\Http\Controllers\api\admin\discount_code;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\DiscountCode;
use App\Models\GeneratedDiscountCode;

class DiscountCodeController extends Controller
{
    public function __construct(private DiscountCode $discount_code,
    private GeneratedDiscountCode $generated_codes){}

    public function view(Request $request){
        $discount_groups = $this->discount_code
        ->get();

        return response()->json([
            "discount_groups" => $discount_groups
        ]);
    }

    public function generated_codes(Request $request, $id){
        $generated_codes = $this->generated_codes
        ->select("code")
        ->where("discount_code_id", $id)
        ->get();

        return response()->json([
            "generated_codes" => $generated_codes
        ]);
    }


    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'group_name' => 'required',
            'usage_number' => 'required|numeric',
            'number_codes' => 'required|numeric',
            'discount' => 'required|numeric',
            'start' => 'required|date',
            'end' => 'required|date',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $discountRequest = $validator->validated();
        $discount_code = $this->discount_code
        ->create($discountRequest);
        $exists_codes = $this->generated_codes
        ->pluck("code")->toArray();
        $new_codes = [];
        $code_item = [];
        while (count($new_codes) < $request->number_codes) {
            $code = str_pad(mt_rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            if(!in_array($code, $exists_codes) && !in_array($code, $new_codes)){
                $new_codes[] = $code;
                $code_item[] = [ 
                    "discount_code_id" => $discount_code->id,
                    "code" => $code,
                    "created_at" => now(),
                    "updated_at" => now(),
                ];
            }
        }
        $this->generated_codes
        ->insert($code_item);

        return response()->json([
            "success" => "You create codes success",
            "codes" => $new_codes
        ]);
    }

    public function delete(Request $request, $id){
        $this->discount_code
        ->where("id", $id)
        ->delete();

        return response()->json([
            "success" => "You delete data success"
        ]);
    }
}
