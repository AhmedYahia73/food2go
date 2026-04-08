<?php

namespace App\Http\Controllers\api\admin\settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\image;

use App\Models\Geidia;
use App\Models\PaymentMethod;

class PaymentMethodGeidiaController extends Controller
{
    use image;

    public function view(Request $request){
        $gedia = PaymentMethod::
        whereHas("geidea")
        ->with("geidea")
        ->first();
 
        return response()->json([
            "name" => $gedia?->name ?? null,
            "logo" => $gedia?->logo_link ?? null,
            "status" => $gedia?->status ?? null,
            "geidea_public_key" => $gedia?->geidea?->geidea_public_key ?? null,
            "api_password" => $gedia?->geidea?->api_password ?? null,
            "environment" => $gedia?->geidea?->environment ?? null,
        ]); 
    }
    
    public function status(Request $request){
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        PaymentMethod::
        whereHas("geidea")
        ->update([
            "status" => $request->status
        ]);

        return response()->json([
            'success' => 'You update status success'
        ]);
    }

    public function update(Request $request){ 
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'logo' => 'sometimes',
            'status' => 'required|boolean',
            'geidea_public_key' => 'required',
            'api_password' => 'required',
            'environment' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $payment_method = PaymentMethod::
        whereHas("geidea")
        ->first();
        if($payment_method){
            $payment_method->name = $request->name;
            $payment_method->status = $request->status;
            if($request->logo){
                $image_path = $this->upload($request, 'logo', 'admin/settings/payment_methods');
                $this->deleteImage($payment_method->logo);
                $payment_method->logo = $image_path;
            }
            $payment_method->save();
            Geidia::
            where("payment_method_id", $payment_method->id)
            ->update([
                "geidea_public_key" => $request->geidea_public_key,
                "api_password" => $request->api_password,
                "environment" => $request->environment,
            ]);
        }
        else{
            if(!$request->logo){
                return response()->json([
                    "errors" => "Logo is required"
                ], 400);
            }
            $image_path = $this->upload($request, 'logo', 'admin/settings/payment_methods');
            $payment_method = PaymentMethod::create([
                "id" => 1000,
                "name" => $request->name,
                "status" => $request->status,
                "type" => "automatic",
                "logo" => $image_path,
            ]);
            Geidia::create([
                "geidea_public_key" => $request->geidea_public_key,
                "api_password" => $request->api_password,
                "environment" => $request->environment,
                "payment_method_id" => $payment_method->id,
            ]);
        }


        return response()->json([
            'success' => 'You update data success'
        ]);
    }
}
