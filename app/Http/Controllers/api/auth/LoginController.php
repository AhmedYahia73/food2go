<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\auth\LoginRequest;
use App\Http\Requests\auth\SignupRequest;
use Illuminate\Support\Facades\Http;
use Google_Client;

use App\Models\Admin;
use App\Models\Delivery;
use App\Models\CaptainOrder;
use App\Models\CashierMan;
use App\Models\User;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Address;
use App\Models\CashierShift;
use App\Models\Zone;
use App\Models\SmsBalance;

class LoginController extends Controller
{
    public function __construct(private Admin $admin, private Delivery $delivery, 
    private User $user, private Branch $branch, private Setting $settings,
    private Address $address, private Zone $zones, private CaptainOrder $captain_order,
    private CashierMan $cashier, private CashierShift $cashier_shift, private SmsBalance $sms_balance,
    ){}

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
            $user = $this->branch
            ->where('email', $request->email)
            ->orWhere('phone', $request->email)
            ->first();
            $user->role = 'branch';
            $role = 'branch';
        }
        else{
            $role = 'admin';
            $user->role = 'admin';
        }
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
            $user->token = $user->createToken('admin')->plainTextToken;
            return response()->json([
                'admin' => $user,
                'token' => $user->token,
                'role' => $role,
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
        // shift_number => sometimes
        $user = $this->cashier
        ->where('user_name', $request->user_name)
        ->with('branch')
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
        if ($request->shift_number && is_numeric($request->shift_number)) {
            $shift_number = $request->shift_number;
        }
        else{
            $cashier = $this->cashier_shift
            ->max('shift') ?? 0;
            $shift_number = $cashier + 1;
            $this->cashier_shift
            ->create([
                'shift' => $shift_number,
                'start_time' => now(),
                'cashier_man_id' => $user->id,
            ]);
        }
        $user->shift_number = $shift_number;
        $user->save();
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
        if (empty($sms_subscription) || $msg_number->balance < 0) {
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
            
            $response = Http::get('https://clientbcknd.food2go.online/admin/v1/my_domain_package')->body();
            $response = json_decode($response);
            $subscription = collect($response?->user_subscription) ?? collect([]); 
            $subscription = $subscription->where('back_link', url(''))
			->where('from', '<=', date('Y-m-d'))->where('to', '>=', date('Y-m-d'))
			->first();  
            if (empty($subscription)) {
                return response()->json([
                    'errors' => 'Application is stoping now'
                ], 400);
            } 
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

    public function sign_up_google(Request $request){
        $validation = Validator::make($request->all(),[
            'id_token' => 'required',
            'client_id' => 'required',
            'phone' => 'required|unique:table,column,except,id',
        ]);
        if($validation->fails()){
            return response()->json(['message'=>$validation->errors()],400);
        }

        $client = new Google_Client(['client_id' => $request->client_id]); // ضع Google Client ID الخاص بتطبيقك
        $payload = $client->verifyIdToken($request->id_token);
    
        if (!$payload) {
            return response()->json(['error' => 'Invalid Google token'], 400);
        }

        $user = User::updateOrCreate(
            ['email' => $payload['email']],
            [
                'f_name' => $payload['name'],
                'phone' => $request->phone,
                'google_id' => $payload['sub'], // unique ID from Google
                'image' => $payload['picture'] ?? null,
            ]
        );
 
        $token = $user->createToken('user_google')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function login_google(Request $request){
       $validation = Validator::make($request->all(),[
            'id_token' => 'required',
            'client_id' => 'required',
        ]);
        if($validation->fails()){
            return response()->json(['message'=>$validation->errors()],400);
        }

        $client = new Google_Client(['client_id' => $request->client_id]); // ضع Google Client ID الخاص بتطبيقك
        $payload = $client->verifyIdToken($request->id_token);
    
        if (!$payload) {
            return response()->json(['error' => 'Invalid Google token'], 400);
        }

        $user = User::where('email', $payload['email'])->first();

        if (!$user) {
            return response()->json(['error' => 'User not registered'], 400);
        }

        $token = $user->createToken('user_google')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
        ]);
    }
}
