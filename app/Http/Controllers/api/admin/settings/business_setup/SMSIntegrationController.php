<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\SmsIntegration;

class SMSIntegrationController extends Controller
{
    public function __construct(private SmsIntegration $sms_integration){}

    public function view(){
        $sms_integration = $this->sms_integration
        ->orderByDesc('id')
        ->first();

        return response()->json([
            'sms_integration' => $sms_integration
        ]);
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'user' => ['required'],
            'pwd' => ['required'],
            'senderid' => ['required'],
            'mobileno' => ['required'],
            'msgtext' => ['required'],
            'CountryCode' => ['required'],
            'profileid' => ['required'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
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
                'msgtext' => $request->msgtext,
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
                'msgtext' => $request->msgtext,
                'CountryCode' => $request->CountryCode,
                'profileid' => $request->profileid,
            ]);
        }

        return response()->json([
            'success' => 'you update data success'
        ]);
    }
}
