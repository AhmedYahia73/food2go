<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\auth\LoginRequest;
use App\Http\Requests\auth\SignupRequest;

use App\Models\Admin;
use App\Models\Delivery;
use App\Models\CaptainOrder;
use App\Models\CashierMan;
use App\Models\User;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Address;
use App\Models\Zone;

class LoginController extends Controller
{
    public function __construct(private Admin $admin, private Delivery $delivery, 
    private User $user, private Branch $branch, private Setting $settings,
    private Address $address, private Zone $zones, private CaptainOrder $captain_order,
    private CashierMan $cashier){}

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

    public function captain_login(LoginRequest $request){
        // /api/captain/auth/login
        // Keys
        // email, password
        $user = $this->captain_order
        ->where('email', $request->email)
        ->orWhere('phone', $request->email)
        ->first();
        if (empty($user)) {
            return response()->json([
                'faield' => 'This user does not have the ability to login'
            ], 405);
        }
        // if ($user->status == 0) {
        //     return response()->json([
        //         'falid' => 'admin is banned'
        //     ], 400);
        // }
        if (password_verify($request->input('password'), $user->password)) {
            $user->role = 'captain_order';
            $user->token = $user->createToken('captain_order')->plainTextToken;
            return response()->json([
                'captain_order' => $user,
                'token' => $user->token,
            ], 200);
        }
        else { 
            return response()->json(['faield'=>'creational not Valid'],403);
        }
    }

    public function cashier_login(Request $request){
        // /api/cashier/auth/login
        // Keys
        // user_name, password
        $user = $this->cashier
        ->where('user_name', $request->user_name)
        ->with('branch', 'cashier')
        ->first();
        if (empty($user)) {
            return response()->json([
                'faield' => 'This user does not have the ability to login'
            ], 405);
        }
        if ($user->status == 0) {
            return response()->json([
                'falid' => 'cashier is banned'
            ], 400);
        }
        if (password_verify($request->input('password'), $user->password)) {
            $user->role = 'cashier';
            $user->token = $user->createToken('cashier')->plainTextToken;
            return response()->json([
                'cashier' => $user,
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
            $addresses = $this->user
            ->where('id', $user->id)
            ->with('address.zone')
            ->first()->address ?? []; 
            $zones = $this->zones->get();

            $user->role = $role;
            $user->token = $user->createToken($user->role)->plainTextToken;
            return response()->json([
                'user' => $user,
                'token' => $user->token,
                'addresses' => $addresses,
                'zones' => $zones,
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
