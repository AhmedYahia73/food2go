<?php

namespace App\Http\Controllers\api\customer\otp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\Mail\OTPMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

use App\Models\User;

class OtpController extends Controller
{
    public function __construct(private User $user){}

    public function create_code(Request $request){
        // https://bcknd.food2go.online/customer/otp/create_code
        // Keys
        // email, phone

        $code = rand(10000, 99999);
        $user_codes = $this->user->get()->pluck('code')->toArray();
        while (in_array($code, $user_codes)) {
            $code = rand(10000, 99999);
        }
        $user = $this->user
        ->where('email', $request->email)
        ->orWhere('phone', $request->phone)
        ->first();
        if (empty($user)) {
            return response()->json([
                'faild' => 'Email is wrong'
            ], 400);
        }
        $user->code = $code;
        $user->save();
        if ($request->email) {
            $data = [
                'code' => $code,
                'name' => $user->f_name . ' ' . $user->l_name
            ];
            Mail::to($user->email)->send(new OTPMail($data));
        } 
        elseif($request->phone) {
            $temporaryToken = Str::random(40);
            $otp = rand(10000, 99999);  // Generate OTP
            $phone = $request->phone;
            $user = $this->user
            ->where('phone', $request->phone)
            ->update([
                'code' => $otp
            ]);
        
            // Send OTP to the new user
            $this->sendOtp($phone, $otp);
        }
        else{
            return response()->json([
                'errors' => 'Phone or email is requred'
            ], 400);
        }
        
        return response()->json([
            'code' => $code,
        ]);
    }

    private function sendOtp($phone, $otp)
    {
        // Send OTP using Mobishastra API
        try {
            $sms_integration = $this->sms_integration
            ->orderByDesc('id')
            ->first();
            $response = Http::timeout(30)->get('http://mshastra.com/sendurl.aspx', [
                'user' => $sms_integration->user,
                'pwd' => $sms_integration->pwd,
                'senderid' => $sms_integration->senderid,
                'mobileno' => $phone,
                'msgtext' => "Your activation number: " . $otp,
                'CountryCode' => $sms_integration->CountryCode,
                'profileid' => $sms_integration->profileid,
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

    public function check_code(Request $request){
        // https://bcknd.food2go.online/customer/otp/check_code
        // Keys
        // email, code
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'code' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        
        $user = $this->user
        ->where('email', $request->email)
        ->where('code', $request->code)
        ->orWhere('phone', $request->email)
        ->where('code', $request->code)
        ->first();
        if (!empty($user)) {
            return response()->json([
                'success' => 'code is true',
            ], 200);
        } else {
            return response()->json([
                'faild' => 'code is false',
            ], 400);
        }
    }

    public function change_password(Request $request){
        // https://bcknd.food2go.online/customer/otp/change_password
        // Keys
        // email, password
        $validator = Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required',
            'code' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
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
        $user->role = 'customer';
        $user->token = $user->createToken('customer')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $user->token,
        ], 200);
    }
}
