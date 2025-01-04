<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\auth\LoginRequest;
use App\Http\Requests\auth\SignupRequest;

use App\Models\Admin;
use App\Models\Delivery;
use App\Models\User;
use App\Models\Branch;
use App\Models\Setting;

class LoginController extends Controller
{
    public function __construct(private Admin $admin, private Delivery $delivery, 
    private User $user, private Branch $branch, private Setting $settings){}

    public function admin_login(LoginRequest $request){
        // https://bcknd.food2go.online/api/admin/auth/login
        // Keys
        // email, password
        $user = $this->admin
        ->where('email', $request->email)
        ->orWhere('phone', $request->email)
        ->with('user_positions.roles')
        ->first();
        if (empty($user)) {
            return response()->json([
                'faield' => 'This user does not have the ability to login'
            ], 405);
        }
        if ($user->status == 0) {
            return response()->json([
                'falid' => 'admin is banned'
            ], 400);
        }
        if (password_verify($request->input('password'), $user->password)) {
            $user->role = 'admin';
            $user->token = $user->createToken('admin')->plainTextToken;
            return response()->json([
                'admin' => $user,
                'token' => $user->token,
            ], 200);
        }
        else { 
            return response()->json(['faield'=>'creational not Valid'],403);
        }
    }
    
    public function login(LoginRequest $request){
        // https://bcknd.food2go.online/api/user/auth/login
        // Keys
        // email, password
        $user = $this->delivery
        ->where('email', $request->email)
        ->orWhere('phone', $request->email)
        ->first();
        $role = 'delivery';
        if (empty($user)) {
            $user = $this->user
            ->where('email', $request->email)
            ->orWhere('phone', $request->email)
            ->first();
            $role = 'customer';
        }
        if (empty($user)) {
            $user = $this->branch
            ->where('email', $request->email)
            ->orWhere('phone', $request->email)
            ->first();
            $role = 'branch';
        }
        if (empty($user)) {
            return response()->json([
                'faield' => 'This user does not have the ability to login'
            ], 405);
        }
        if ($user->status == 0) {
            return response()->json([
                'falid' => 'user is banned'
            ], 400);
        }
        if (password_verify($request->input('password'), $user->password)) {
            $user->role = $role;
            $user->token = $user->createToken($user->role)->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $user->token,
            ], 200);
        }
        else { 
            return response()->json(['faield'=>'creational not Valid'],403);
        }
    }

    public function logout(Request $request){
        // https://bcknd.food2go.online/api/logout
        $user =auth()->user();
        $deletToken = $user->tokens()->delete();
        if ($deletToken) {
            return response()->json([
                'success' => 'You logout success'
            ]);
        } else {
            return response()->json([
                'faild' => 'You faild to logout'
            ], 400);
        }
        
    }
}
