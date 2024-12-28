<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\auth\SignupRequest;
use App\Mail\OTPMail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\trait\image;

use App\Models\User;

class SignupController extends Controller
{
    public function __construct(private User $user){}
    protected $userRequest = [
        'f_name',
        'l_name',
        'email',
        'phone',
        'password',
    ];
    use image;

    public function signup(SignupRequest $request){
        // https://bcknd.food2go.online/api/user/auth/signup
        // Keys
        // f_name, l_name, email, phone, password
        $data = $request->only($this->userRequest);
        $user = $this->user->create($data);
        $user->image = null;
        $user->role = 'customer';
        $user->token = $user->createToken('customer')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $user->token,
        ], 200);
    }
        
    public function code(Request $request){
        // https://bcknd.food2go.online/api/user/auth/signup/code
        // keys
        // email
        $validator = Validator::make($request->all(), [ 
            'email' => 'required|email|unique:users,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $code = rand(10000, 99999);
        $data['code'] = $code;
        Mail::to($request->email)->send(new OTPMail($data));

        return response()->json([
            'code' => $code
        ]);
    }
}
