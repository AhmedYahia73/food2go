<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\auth\SignupRequest;
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
        // backend.food2go.pro/api/user/auth/signup
        // Keys
        // f_name, l_name, email, phone, password
        $data = $request->only($this->userRequest);
        $user = $this->user->create($data);
        $user->role = 'customer';
        $user->token = $user->createToken('customer')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $user->token,
        ], 200);
    }
}
