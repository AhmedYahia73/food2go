<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Setting;
use App\Models\EmailIntegration;
use App\Models\SmsIntegration;

class CustomerLoginController extends Controller
{
    public function __construct(private Setting $settings,
    private EmailIntegration $email_integration, 
    private SmsIntegration $sms_integration){}

    public function view(){
        // https://bcknd.food2go.online/admin/settings/business_setup/customer_login
        $customer_login = $this->settings
        ->where('name', 'customer_login')
        ->orderByDesc('id')
        ->first();
        if (empty($customer_login)) {
            $setting = ['login' => 'manuel', 'verification' => null];
            $setting = json_encode($setting);
            $customer_login = $this->settings
            ->create([
                'name' => 'customer_login',
                'setting' => $setting
            ]);
        }
        $email_integration = $this->email_integration
        ->orderByDesc('id')
        ->first();
        $sms_integration = $this->sms_integration
        ->orderByDesc('id')
        ->first();

        return response()->json([
            'customer_login' => $customer_login,
            'email_integration' => $email_integration,
            'sms_integration' => $sms_integration,
        ]);
    }

    public function add(Request $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/customer_login/add
        // {"login": "manuel","verification": "email"}
        // login => [manuel, otp], verification => [email, phone]
        // verification => [email, phone], 
        // email, integration_password
        // user, pwd, senderid, mobileno, msgtext, CountryCode, profileid
        $validator = Validator::make($request->all(), [
            'verification' => ['required', 'in:email,phone'],
            'email' => ['required_if:verification,email', 'email'],
            'integration_password' => ['required_if:verification,email'],
            'user' => ['required_if:verification,phone'],
            'pwd' => ['required_if:verification,phone'],
            'senderid' => ['required_if:verification,phone'],
            'mobileno' => ['required_if:verification,phone'],
            'CountryCode' => ['required_if:verification,phone'],
            'profileid' => ['required_if:verification,phone'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $setting = [
            'login' => 'otp',
            'verification' => $request->verification ?? null,
        ];
        $setting = json_encode($setting);
        $customer_login = $this->settings
        ->where('name', 'customer_login')
        ->orderByDesc('id')
        ->first();
        if (empty($customer_login)) {
            $customer_login = $this->settings
            ->create([
                'name' => 'customer_login',
                'setting' => $setting
            ]);
        } 
        else{
            $customer_login->update([
                'setting' => $setting
            ]);
        }
        if ($request->verification == 'email') {
            $email_integration = $this->email_integration
            ->orderByDesc('id')
            ->first();
            if (empty($email_integration)) {
                $this->email_integration
                ->create([
                    'email' => $request->email,
                    'integration_password' => $request->integration_password,
                ]);
            } 
            else {
                $email_integration
                ->update([
                    'email' => $request->email,
                    'integration_password' => $request->integration_password,
                ]);
            }
        }
        elseif ($request->verification == 'phone') {
            $sms_integration = $this->sms_integration
            ->orderByDesc('id')
            ->first();
            if (empty($sms_integration)) {
                $this->sms_integration
                ->create([
                    'user' => $request->user,
                    'pwd' => $request->pwd,
                    'senderid' => $request->senderid,
                    'mobileno' => $request->mobileno,
                    'CountryCode' => $request->CountryCode,
                    'profileid' => $request->profileid,
                ]);
            } 
            else {
                $sms_integration
                ->update([
                    'user' => $request->user,
                    'pwd' => $request->pwd,
                    'senderid' => $request->senderid,
                    'mobileno' => $request->mobileno,
                    'CountryCode' => $request->CountryCode,
                    'profileid' => $request->profileid,
                ]);
            }
        }

        return response()->json([
            'customer_login' => $customer_login,
            'request' => $request->all(),
        ]);
    }
}
