<?php

namespace App\Http\Controllers\api\customer\otp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\Mail\OTPMail;
use Illuminate\Support\Facades\Mail;

use App\Models\User;

class OtpController extends Controller
{
    public function __construct(private User $user){}

    public function create_code(Request $request){
        // https://backend.food2go.pro/customer/otp/create_code
        $code = rand(10000, 99999);
        $user_codes = $this->user->get()->pluck('code')->toArray();
        while (in_array($code, $user_codes)) {
            $code = rand(10000, 99999);
        }
        $user = $request->user();
        $user->code = $code;
        $user->save();
        $data = [
            'code' => $code,
            'name' => $user->f_name . ' ' . $user->l_name
        ];
        Mail::to($user->email)->send(new OTPMail($data));
    

        return response()->json([
            'code' => $code,
        ]);
    }

    public function change_password(Request $request){
        // https://backend.food2go.pro/customer/otp/change_password
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $user = $request->user();
        if ($request->code != $user->code || empty($user->code)) {
            return response()->json([
                'faild' => 'Code is wrong'
            ], 400);
        }
        $user->password = $request->password;
        $user->code = null;
        $user->save();

        return response()->json([
            'success' => 'You change password success'
        ]);
    }
}
