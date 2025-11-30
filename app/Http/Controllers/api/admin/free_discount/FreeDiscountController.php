<?php

namespace App\Http\Controllers\api\admin\free_discount;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\DiscountEmail;
use App\Models\Setting;

class FreeDiscountController extends Controller
{
    public function __construct(private DiscountEmail $discount_email,
    private Setting $settings){}

    public function view(Request $request){
        $max_discount_order = $this->settings
        ->where("name", "max_discount_order")
        ->first()
        ->setting ?? 0;
        $max_discount_shift = $this->settings
        ->where("name", "max_discount_shift")
        ->first()
        ->setting ?? 0;
        $emails = $this->discount_email
        ->select("id", "email")
        ->get();

        return response()->json([
            "max_discount_order" => $max_discount_order,
            "max_discount_shift" => $max_discount_shift,
            "emails" => $emails,
        ]);
    }

    public function create_update(Request $request){
        $validator = Validator::make($request->all(), [
            'max_discount_order' => 'required|numeric',
            'max_discount_shift' => 'required|numeric',
            'emails' => 'required|array',
            'emails.*' => 'required|email|unique:discount_emails,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $max_discount_order = $this->settings
        ->where("name", "max_discount_order")
        ->first();
        $max_discount_shift = $this->settings
        ->where("name", "max_discount_shift")
        ->first();
        if(empty($max_discount_order)){
            $this->settings
            ->create([
                "name" => "max_discount_order",
                "setting" => $request->max_discount_order,
            ]);
        }
        else{
            $max_discount_order->setting = $request->max_discount_order;
            $max_discount_order->save();
        }
        if(empty($max_discount_shift)){
           $this->settings
            ->create([
                "name" => "max_discount_shift",
                "setting" => $request->max_discount_shift,
            ]);
        }
        else{
            $max_discount_shift->setting = $request->max_discount_shift;
            $max_discount_shift->save();
        }
        $this->discount_email
        ->delete();
        foreach ($request->emails as $item) {
            $this->discount_email
            ->create([
                "email" => $item
            ]);
        }

        return response()->json([
            "success" => "You update data success"
        ]);
    }
}
