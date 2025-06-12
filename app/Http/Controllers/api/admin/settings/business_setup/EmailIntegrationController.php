<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\EmailIntegration;

class EmailIntegrationController extends Controller
{
    public function __construct(private EmailIntegration $email_integration){}

    public function view(){
        $email_integration = $this->email_integration
        ->orderByDesc('id')
        ->first();

        return response()->json([
            'email_integration' => $email_integration
        ]);
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => 'required|',
            '' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
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
        

        return response()->json([
            'success' => 'you update data success'
        ]);
    }
}
