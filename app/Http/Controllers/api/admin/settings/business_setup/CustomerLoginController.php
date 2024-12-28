<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Setting;

class CustomerLoginController extends Controller
{
    public function __construct(private Setting $settings){}

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

        return response()->json([
            'customer_login' => $customer_login,
        ]);
    }

    public function add(Request $request){
        // https://bcknd.food2go.online/admin/settings/business_setup/customer_login/add
        // {"login": "manuel","verification": "email"}
        // login => [manuel, otp], verification => [email, phone]
        $validator = Validator::make($request->all(), [
            'login' => 'required|in:manuel,otp',
            'verification' => 'in:email,phone',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $setting = [
            'login' => $request->login,
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

        return response()->json([
            'customer_login' => $customer_login,
            'request' => $request->all(),
        ]);
    }
}
