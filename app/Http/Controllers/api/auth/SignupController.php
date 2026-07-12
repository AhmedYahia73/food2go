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
use Exception;

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
        if(empty($customer_login)&& empty($request->email)){ 
            return response()->json([
                "errors" => "email is required"
            ], 400); 
        }
        else{
            $customer_login = json_decode($customer_login);
            $customer_login = $customer_login->verification;
            if($customer_login == "email" && empty($request->email)){
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
    }public function otp_phone(Request $request)
    {
        // https://bcknd.food2go.online/api/user/auth/signup/phone_code
        // keys: phone, email
        $validator = Validator::make($request->all(), [
            'email' => 'email', 
        ]);
        
        if ($validator->fails()) { 
            return response()->json([
                'errors' => $validator->errors(),
            ], 400);
        }

        if ($request->email) {
            $code = rand(10000, 99999);
            $data['code'] = $code;
            Mail::to($request->email)->send(new OTPMail($data));
            
            return response()->json([
                'code' => $code
            ]);
        } 
        elseif ($request->phone) {
            $temporaryToken = Str::random(40);
            $code = rand(10000, 99999);  // Generate OTP
            $phone = $request->phone;
            
            $this->user
                ->where('phone', $request->phone)
                ->update([
                    'code' => $code
                ]);
        
            // هنا بنمرر الـ phone والـ code للفانكشن وبنرجع الرد بتاعها مباشرة
            return $this->sendOtp($phone, $code);
        }
        else {
            return response()->json([
                'errors' => 'Phone or email is required'
            ], 400);
        }
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

            // تأكيد أن السجل موجود قبل استخدامه
            if (!$sms_subscription) {
                throw new \Exception('SMS Subscription not found or expired for this domain.');
            }

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
                if (empty($customer_login)) {
                    $this->settings
                        ->create([
                            'name' => 'customer_login',
                            'setting' => '{"login":"otp","verification":"email"}',
                        ]);
                }
                else {
                    $customer_login->update([
                        'setting' => '{"login":"otp","verification":"email"}',
                    ]);
                }
                
                throw new \Exception('SMS Balance is insufficient.');
            }

            $this->sms_balance
                ->where('package_id', $sms_subscription->id)
                ->update([
                    'balance' => $msg_number->balance - 1
                ]);

            $sms_integration = $this->sms_integration
                ->orderByDesc('id')
                ->first();

            if (!$sms_integration) {
                throw new \Exception('Mobishastra configuration settings not found in database.');
            }

            // إرسال الطلب الفعلي لـ Mobishastra
            $apiResponse = Http::timeout(30)->get('http://mshastra.com/sendurl.aspx', [
                'user' => $sms_integration->user,
                'pwd' => $sms_integration->pwd,
                'senderid' => $sms_integration->senderid,
                'mobileno' => $phone,
                'msgtext' => "Your activation number: " . $otp,
                'CountryCode' => $sms_integration->CountryCode,
                'profileid' => $sms_integration->profileid,
            ]);

            $responseBody = $apiResponse->body();

            // فحص رد الشركة لتحديد هل نجح فعلاً أم لا
            if ($apiResponse->successful()) {
                // لو الرد فيه أي كلمة تدل على خطأ من سيرفر Mobishastra
                if (str_contains(strtolower($responseBody), 'error') || str_contains(strtolower($responseBody), 'fail')) {
                    return response()->json([
                        'status' => 'failed_from_provider',
                        'message' => 'Mobishastra API rejected the message.',
                        'api_response' => $responseBody
                    ], 400);
                }

                // هنا لو نجح والرد سليم (بيطبع لك الـ Message ID أو كلمة النجاح اللي مبعوتة منهم)
                return response()->json([
                    'status' => 'success',
                    'message' => 'OTP sent successfully.',
                    'api_response' => $responseBody
                ], 200);
            } else {
                // لو الـ status code مش 200
                return response()->json([
                    'status' => 'http_error',
                    'message' => 'Failed to connect with Mobishastra.',
                    'http_code' => $apiResponse->status(),
                    'api_response' => $responseBody
                ], 500);
            }

        } catch (\Throwable $e) {
            // في حالة حدوث أي إيرور داخلي في السيرفر أو الباقة
            return response()->json([
                'status' => 'internal_error',
                'message' => 'An error occurred during processing.',
                'debug_error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
