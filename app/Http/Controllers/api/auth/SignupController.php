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

use App\Models\SmsIntegration;
use App\Models\User;
use App\Models\SmsBalance;
use App\Models\Setting;

class SignupController extends Controller
{
    public function __construct(private User $user,
    private SmsIntegration $sms_integration,
    private SmsBalance $sms_balance, private Setting $settings){}
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
        $customer_login = Setting::
        where("name", "customer_login")
        ->first()?->setting ?? null;
        if(empty($customer_login)){ 
            return response()->json([
                "errors" => "email is required"
            ], 400); 
        }
        else{
            $customer_login = json_decode($customer_login);
            $customer_login = $customer_login->verification;
            if($customer_login == "email"){
                return response()->json([
                    "errors" => "email is required"
                ], 400);
            }
        }
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
            'email' => 'email|unique:users,email',
            'phone' => 'sometimes',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        if ($request->email) {
            $code = rand(10000, 99999);
            $data['code'] = $code;
            Mail::to($request->email)->send(new OTPMail($data));
        } 
        elseif($request->phone) {
            $temporaryToken = Str::random(40);
            $code = rand(10000, 99999);  // Generate OTP
            $phone = $request->phone;
            $user = $this->user
            ->where('phone', $request->phone)
            ->update([
                'code' => $code
            ]);
        
            // Send OTP to the new user
            $this->sendOtp($phone, $code);
        }
        else{
            return response()->json([
                'errors' => 'Phone or email is requred'
            ], 400);
        }
        return response()->json([
            'code' => $code
        ]);
    }

    public function otp_phone(Request $request)
    {
        // https://bcknd.food2go.online/api/user/auth/signup/phone_code
        // keys
        // phone, email
        $validator = Validator::make($request->all(), [
            'email' => 'email', 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        if ($request->email) {
            $code = rand(10000, 99999);
            $data['code'] = $code;
            Mail::to($request->email)->send(new OTPMail($data));
        } 
        elseif($request->phone) {
            $temporaryToken = Str::random(40);
            $code = rand(10000, 99999);  // Generate OTP
            $phone = $request->phone;
            $user = $this->user
            ->where('phone', $request->phone)
            ->update([
                'code' => $code
            ]);
        
            // Send OTP to the new user
            $this->sendOtp($phone, $code);
        }
        else{
            return response()->json([
                'errors' => 'Phone or email is requred'
            ], 400);
        }
        return response()->json([
            'code' => $code
        ]);
    }
    
    private function sendOtp($phone, $otp)
    {
        // Send OTP using Mobishastra API
        try {
           $response = Http::get('https://clientbcknd.food2go.online/admin/v1/my_sms_package')->body();
            $response = json_decode($response);
    
            $sms_subscription = collect($response?->user_sms) ?? collect([]); 
            $sms_subscription = $sms_subscription->where('back_link', url(''))
            ->where('from', '<=', date('Y-m-d'))->where('to', '>=', date('Y-m-d'))
            ->first();
            $msg_number = $this->sms_balance
            ->where('package_id', $sms_subscription?->id)
            ->first();
            if (!empty($sms_subscription) && empty($msg_number)) {
                $msg_number = $this->sms_balance
                ->create([
                    'package_id' => $sms_subscription->id,
                    'balance' => $sms_subscription->msg_number,
                ]);
            }
            if (empty($sms_subscription) || $msg_number->balance <= 0) {
                $customer_login = $this->settings
                ->where('name', 'customer_login')
                ->first();
                if(empty($customer_login)){
                    $this->settings
                    ->create([
                        'name' => 'customer_login',
                        'setting' => '{"login":"otp","verification":"email"}',
                    ]);
                }
                else{
                    $customer_login->update([
                        'setting' => '{"login":"otp","verification":"email"}',
                    ]);
                }
            }
            $this->sms_balance
            ->where('package_id', $sms_subscription->id)
            ->update([
                'balance' => $msg_number->balance - 1
            ]);
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
    
                return response()->json(['message' => 'OTP sent successfully.'], 200);
            } else {
                throw new Exception('Failed to send OTP.');
            }
        } catch (\Throwable $e) {
            return response()->json(['errors' => 'Unable to send OTP at this time. Please try again later.'], 500);
        }
    }
}
