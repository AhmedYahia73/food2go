<?php

namespace App\Http\Controllers\api\cashier\profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function __construct(){}

    public function view(){
        
    }

    public function update(Request $request){
        $validator = Validator::make($request->all(), [
            'user_name' => 'sometimes',
            'password' => 'sometimes',
            'status' => 'sometimes|boolean',
            'image' => 'sometimes',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
    }
}
