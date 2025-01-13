<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\auth\SignupRequest;
use App\Mail\OTPMail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use App\trait\image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

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

    public function otp_phone(Request $request)
    {
        // https://bcknd.food2go.online/api/user/auth/signup/phone_code
        // keys
        // phone
        $validator = Validator::make($request->all(), [ 
            'phone' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $temporaryToken = Str::random(40);
        $otp = rand(10000, 99999);  // Generate OTP
        $phone = $request->phone;
        $user = $this->user
        ->where('phone', $request->phone)
        ->update([
            'code' => $request->code
        ]);
    
        // Send OTP to the new user
        $this->sendOtp($phone, $otp);
    
        return response()->json([
            'message' => 'OTP sent successfully.', 
            'phone' => $phone,
            'code' => $otp
        ], 201);
    }
    
    private function sendOtp($phone, $otp)
    {
        // Send OTP using Mobishastra API
        try {
            $response = Http::timeout(30)->get('http://mshastra.com/sendurl.aspx', [
                'user' => '20101263',
                'pwd' => 'L@m@d@2005',
                'senderid' => 'Lamada',
                'mobileno' => $phone,
                'msgtext' => "Your activation number: " . $otp,
                'CountryCode' => '+20',
                'profileid' => '20101263',
            ]);
    
            if ($response->successful()) {
                // Store the OTP in the database
                $user->otp()->create(['otp' => $otp]);
    
                return response()->json(['message' => 'OTP sent successfully.'], 200);
            } else {
                throw new Exception('Failed to send OTP.');
            }
        } catch (\Throwable $e) {
            return response()->json(['errors' => 'Unable to send OTP at this time. Please try again later.'], 500);
        }
    }
}
