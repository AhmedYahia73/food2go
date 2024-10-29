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
        // Keys
        // email
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $code = rand(10000, 99999);
        $user_codes = $this->user->get()->pluck('code')->toArray();
        while (in_array($code, $user_codes)) {
            $code = rand(10000, 99999);
        }
        $user = $this->user
        ->where('email', $request->email)
        ->orWhere('phone', $request->email)
        ->first();
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
        // Keys
        // code, email, password
        $validator = Validator::make($request->all(), [
            'code' => 'required',
            'email' => 'required',
            'password' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $user = $this->user
        ->where('email', $request->email)
        ->where('code', $request->code)
        ->orWhere('phone', $request->email)
        ->where('code', $request->code)
        ->first();
        if (empty($user)) {
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
