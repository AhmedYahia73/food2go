<?php

namespace App\Http\Controllers\api\auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\auth\LoginRequest;
use App\Http\Requests\auth\SignupRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Google_Client;

use App\Models\MainData; 
use App\Models\Admin;
use App\Models\Delivery;
use App\Models\CaptainOrder;
use App\Models\CashierMan;
use App\Models\Cashier;
use App\Models\User;
use App\Models\Branch;
use App\Models\Setting;
use App\Models\Address;
use App\Models\CashierShift;
use App\Models\Kitchen;
use App\Models\Zone;
use App\Models\SmsBalance;
use App\Models\Waiter;
use App\Models\DeviceToken;
use App\Models\StorageMan;
use App\Models\FinantiolAcounting; 
use App\Models\CompanyInfo;
use App\Models\PreparationMan;
use App\Models\ReceiptDesign;
use App\Models\Order;

class LoginController extends Controller
{
    public function __construct(private Admin $admin, private Delivery $delivery, 
    private User $user, private Branch $branch, private Setting $settings,
    private Address $address, private Zone $zones, private CaptainOrder $captain_order,
    private CashierMan $cashier, private CashierShift $cashier_shift, private SmsBalance $sms_balance,
    private Kitchen $kitchen, private Waiter $waiter, private StorageMan $store_man_model,
    private Cashier $cashier_machine, private FinantiolAcounting $financial_account,
    private CompanyInfo $company_info, private PreparationMan $preparation_man,
    private Order $orders
    ){}

    public function store_man(Request $request){
        // kitchen
        $validation = Validator::make($request->all(), [
            'user_name' => 'required', 
            'password' => 'required', 
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 422);
        }

        $user = $this->store_man_model
        ->where('user_name', $request->user_name)
        ->first();
        if (empty($user)) {
            return response()->json([
                'errors' => 'This Store Man does not have the ability to login'
            ], 405);
        }
        if (password_verify($request->input('password'), $user->password)) {
            $user->token = $user->createToken('store_man')->plainTextToken;
            $role = $user->role;
            return response()->json([
                'store_man' => $user,
                'token' => $user->token, 
                'role'  => $role,
            ], 200);
        }
        else {
            return response()->json(['errors'=>'creational not Valid'],403);
        }
    }

    public function preparation_man_login(Request $request){
        // preparation_man
        $validation = Validator::make($request->all(), [
            'name' => 'required', 
            'password' => 'required', 
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 422);
        }

        $user = $this->preparation_man
        ->where('name', $request->name)
        ->first();
        if (empty($user)) {
            return response()->json([
                'errors' => 'This preparation man does not have the ability to login'
            ], 405);
        }
        if (password_verify($request->input('password'), $user->password)) {
            $user->token = $user->createToken('preparation_man')->plainTextToken;
            $role = $user->type;
            $app_setup = MainData::
            first();
            $app_setup = [
                "name" => $app_setup?->name ?? null,
                "ar_name" => $app_setup?->translations()
                ?->where("locale", "ar")->where("key", $app_setup?->name)
                ->first()?->value ?? $app_setup?->name ,
                "first_color" => $app_setup?->first_color ?? null,
                "second_color" => $app_setup?->second_color ?? null,
                "third_color" => $app_setup?->third_color ?? null,
                "instagram" => $app_setup?->instagram ?? null,
                "facebook" => $app_setup?->facebook ?? null,
                "logo" => $app_setup?->logo_link ?? null,
                'image_1' => $app_setup?->image1_link ?? null,
                'image_2' => $app_setup?->image2_link ?? null,
                'image_3' => $app_setup?->image3_link ?? null,
                'image_4' => $app_setup?->image4_link ?? null,
                'image_5' => $app_setup?->image5_link ?? null,
                'image_6' => $app_setup?->image6_link ?? null,
            ];
            $notification = Setting::where("name", "notification_sound")
            ->first()?->setting;
            $notification = url("storage/" . $notification);
            return response()->json([
                'preparation_man' => $user,
                'token' => $user->token,
                'branch_name' => $user->branch->name,
                'branch_phone' => $user->branch->phone,
                'role'  => $role,
                'app_setup'  => $app_setup,
                "notification" => $notification
            ], 200);
        }
        else {
            return response()->json(['errors'=>'creational not Valid'],403);
        }
    }

    public function kitchen_login(Request $request){
        // kitchen
        $validation = Validator::make($request->all(), [
            'name' => 'required', 
            'password' => 'required', 
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 422);
        }

        $user = $this->kitchen
        ->where('name', $request->name)
        ->first();
        if (empty($user)) {
            return response()->json([
                'errors' => 'This Kitchen does not have the ability to login'
            ], 405);
        }
        if (password_verify($request->input('password'), $user->password)) {
            $user->token = $user->createToken('kitchen')->plainTextToken;
            $role = $user->type;
            $app_setup = MainData::
            first();
            $app_setup = [
                "name" => $app_setup?->name ?? null,
                "ar_name" => $app_setup?->translations()
                ?->where("locale", "ar")->where("key", $app_setup?->name)
                ->first()?->value ?? $app_setup?->name ,
                "first_color" => $app_setup?->first_color ?? null,
                "second_color" => $app_setup?->second_color ?? null,
                "third_color" => $app_setup?->third_color ?? null,
                "instagram" => $app_setup?->instagram ?? null,
                "facebook" => $app_setup?->facebook ?? null,
                "logo" => $app_setup?->logo_link ?? null,
                'image_1' => $app_setup?->image1_link ?? null,
                'image_2' => $app_setup?->image2_link ?? null,
                'image_3' => $app_setup?->image3_link ?? null,
                'image_4' => $app_setup?->image4_link ?? null,
                'image_5' => $app_setup?->image5_link ?? null,
                'image_6' => $app_setup?->image6_link ?? null,
            ];
            $notification = Setting::where("name", "notification_sound")
            ->first()?->setting;
            $notification = url("storage/" . $notification);
            return response()->json([
                'kitchen' => $user,
                'token' => $user->token,
                'branch_name' => $user->branch->name,
                'branch_phone' => $user->branch->phone,
                'role'  => $role,
                'app_setup'  => $app_setup,
                "notification" => $notification
            ], 200);
        }
        else {
            return response()->json(['errors'=>'creational not Valid'],403);
        }
    }

    public function admin_login(LoginRequest $request){
        // https://bcknd.food2go.online/api/admin/auth/login
        // Keys
        // email, password
        // $validation = Validator::make($request->all(), [
        //     'fcm_token' => 'required', 
        // ]);

        // if ($validation->fails()) {
        //     return response()->json($validation->errors(), 422);
        // }
        $user = $this->admin
        ->where('email', $request->email)
        ->orWhere('phone', $request->email)
        ->with('user_positions.roles')
        ->first();
        $r_online_noti = $this->settings
        ->where("name", "r_online_noti")
        ->first()?->setting ?? 0;
        if (empty($user)) {
            $user = $this->branch
            ->where('email', $request->email)
            ->orWhere('phone', $request->email)
            ->first(); 
            $role = 'branch';
        }
        else{
            $role = 'admin'; 
        }
        if (empty($user)) {
            return response()->json([
                'errors' => 'This user does not have the ability to login'
            ], 405);
        }
        if ($user->status == 0) {
            return response()->json([
                'falid' => 'admin is banned'
            ], 400);
        }
        $user->role = $role;
        if (password_verify($request->input('password'), $user->password)) {
            $user->token = $user->createToken('admin')->plainTextToken;
            if($role == 'branch' && $request->fcm_token){
                DeviceToken::updateOrCreate(
                    ['branch_id' => $user->id],
                    ['token' => $request->fcm_token]
                );
            }
            elseif($role == 'admin' && $request->fcm_token){
                DeviceToken::updateOrCreate(
                    ['admin_id' => $user->id],
                    ['token' => $request->fcm_token]
                ); 
            }
            // ____________________________ 
            $order_lang = $this->settings
            ->where("name", "order_lang")
            ->first();
            if (empty($order_lang)) {
                $order_lang = $this->settings
                ->create([
                    'name' => 'order_lang',
                    'setting' => 'ar',
                ]);
            }

            return response()->json([
                'admin' => $user,
                'token' => $user->token,
                'role' => $role,
                "r_online_noti" => $r_online_noti,
                "order_lang" => $order_lang->setting,
            ], 200);
        }
        else { 
            return response()->json(['errors'=>'creational not Valid'],403);
        }
    }

    public function captain_login(Request $request){
        // /api/captain/auth/login
        // Keys
        // user_name, password
        $validation = Validator::make($request->all(), [
            'user_name' => 'required', 
            'password' => 'required', 
            'fcm_token' => 'required', 
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 422);
        }
        $user = $this->captain_order
        ->where('user_name', $request->user_name)
        ->orWhere('phone', $request->user_name)
        ->first();
        if (empty($user)) {
            
            $user = $this->waiter
            ->where('user_name', $request->user_name)
            ->first();
            if (empty($user)) {
                return response()->json([
                    'errors' => 'This user does not have the ability to login'
                ], 405);
            }
            if ($user->status == 0) {
                return response()->json([
                    'errors' => 'waiter is banned'
                ], 400);
            }
            if (password_verify($request->input('password'), $user->password)) {
                $user->fcm_token = $request->fcm_token;
                $user->save();
                $user->role = 'waiter';
                $user->token = $user->createToken('waiter')->plainTextToken;
                return response()->json([
                    'user' => $user,
                    'token' => $user->token,
                    'role' => $user->role,
                ], 200);
            }
            else { 
                return response()->json(['errors'=>'creational not Valid'],403);
            }
        }
        // if ($user->status == 0) {
        //     return response()->json([
        //         'falid' => 'admin is banned'
        //     ], 400);
        // }
        if (password_verify($request->input('password'), $user->password)) {
            $user->fcm_token = $request->fcm_token;
            $user->save();
            $user->role = 'captain_order';
            $user->token = $user->createToken('captain_order')->plainTextToken;
            $preparation_num_status = $this->company_info
            ->first()?->preparation_num_status;

            return response()->json([
                'user' => $user,
                'token' => $user->token,
                'role' => $user->role,
                'preparation_num_status' => $preparation_num_status,
            ], 200);
        }
        else { 
            $user = $this->waiter
            ->where('user_name', $request->user_name)
            ->first();
            if (empty($user)) {
                return response()->json([
                    'errors' => 'This user does not have the ability to login'
                ], 405);
            }
            if ($user->status == 0) {
                return response()->json([
                    'errors' => 'waiter is banned'
                ], 400);
            }
            if (password_verify($request->input('password'), $user->password)) {
                $user->fcm_token = $request->fcm_token;
                $user->save();
                $user->role = 'waiter';
                $user->token = $user->createToken('waiter')->plainTextToken;
                return response()->json([
                    'user' => $user,
                    'token' => $user->token,
                    'role' => $user->role,
                ], 200);
            }
            else { 
                return response()->json(['errors'=>'creational not Valid'],403);
            }
        }
    }

    public function waiter_login(Request $request){
        // /api/captain/auth/login
        // Keys
        // email, password
        $validation = Validator::make($request->all(), [
            'user_name' => 'required', 
            'password' => 'required', 
            'fcm_token' => 'required', 
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 422);
        }
        $user = $this->waiter
        ->where('user_name', $request->user_name)
        ->first();
        if (empty($user)) {
            return response()->json([
                'errors' => 'This user does not have the ability to login'
            ], 405);
        }
        if ($user->status == 0) {
            return response()->json([
                'errors' => 'waiter is banned'
            ], 400);
        }
        if (password_verify($request->input('password'), $user->password)) {
            $user->fcm_token = $request->fcm_token;
            $user->save();
            $user->role = 'waiter';
            $user->token = $user->createToken('waiter')->plainTextToken;
            return response()->json([
                'waiter' => $user,
                'token' => $user->token,
            ], 200);
        }
        else { 
            return response()->json(['errors'=>'creational not Valid'],403);
        }
    }

    public function cashier_login(Request $request){
        // /api/cashier/auth/login
        // Keys
        // user_name, password
        // shift_number => sometimes
        $validation = Validator::make($request->all(), [
            'user_name' => 'required', 
            'password' => 'required',
            "cashier_id" => "exists:cashiers,id",
            // 'fcm_token' => 'required',
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 422);
        }
        $receipt_design = ReceiptDesign::
        first();
        $receipt_design = [
            'logo' => $receipt_design->logo ?? 1,
            'name' => $receipt_design->name ?? 1,
            'address' => $receipt_design->address ?? 1,
            'branch' => $receipt_design->branch ?? 1,
            'phone' => $receipt_design->phone ?? 1,
            'cashier_name' => $receipt_design->cashier_name ?? 1,
            'footer' => $receipt_design->footer ?? 1,
            'taxes' => $receipt_design->taxes ?? 1,
            'services' => $receipt_design->services ?? 1,
            'table_num' => $receipt_design->table_num ?? 1,
            'preparation_num' => $receipt_design->preparation_num ?? 1,
        ];
        $financial_account = $this->financial_account
        ->select('id', 'name', 'details', 'logo', 'description_status', 'discount')
        ->whereHas('branch')
        ->where('status', 1)
        ->get(); 
        $user = $this->cashier
        ->where('user_name', $request->user_name)
        ->with('branch')
        ->first();
        if (empty($user)) {
            return response()->json([
                'errors' => 'This user does not have the ability to login'
            ], 405);
        }
        if ($user->status == 0) {
            return response()->json([
                'falid' => 'cashier is banned'
            ], 400);
        } 
        if (password_verify($request->input('password'), $user->password)) {
            $cashier = $this->cashier
            ->where("cashier_id", $request->cashier_id ?? 0)
            ->where("id", "!=", $user->id)
            ->whereHas("tokens", function ($q) {
                $q->whereNull('expires_at'); 
            })
            ->first();
            $start_shift = false;
            // if(!empty($cashier)){
            //     return response()->json([
            //         'errors' => 'Another Cashier man login at this cashier'
            //     ]);
            // }
            if($request->cashier_id){
                $cashier_shift = $this->cashier_shift
                ->where("cashier_id", $request->cashier_id)
                ->where("cashier_man_id", $user->id)
                ->whereNull("end_time")
                ->first();
                if(!empty($cashier_shift)){
                    $start_shift = true;
                }
                $user->cashier_id = $request->cashier_id;
            }
            // if ($user->tokens()->exists()) {
            //     return response()->json([
            //         'errors' => 'You are already logged in from another device.'
            //     ], 400);
            // }

            $user->fcm_token = $request->fcm_token;
            $user->save();
            $user->role = 'cashier';
            $user->token = $user->createToken('cashier')->plainTextToken;
            $resturant_name = $this->company_info
            ->first();
            $preparation_num_status = $this->company_info
            ->first()?->preparation_num_status; 
            return response()->json([
                'cashier' => $user,
                'token' => $user->token,
                'start_shift' => $start_shift,
                'financial_account' => $financial_account,
                "resturant_name" => $resturant_name?->name,
                "resturant_logo" => url('storage/' . $resturant_name?->logo),
                'preparation_num_status' => $preparation_num_status,
                "receipt_design" => $receipt_design,
            ], 200);
        }
        else {  
            return response()->json(['errors'=>'creational not Valid'],403);
        }
    }

    public function printer_cashier(Request $request, $id){
        $printer = $this->cashier_machine
        ->select("print_name", "print_type", "print_port", "print_ip")
        ->where("id", $id)
        ->first();

        return response()->json([
            "printer" => $printer
        ]);
    }

    public function start_shift(Request $request){
        $validation = Validator::make($request->all(), [
            "cashier_id" => "exists:cashiers,id",
            // 'fcm_token' => 'required',
        ]);
        if ($validation->fails()) {
            return response()->json($validation->errors(), 422);
        }
        $cashier = $this->cashier_shift
        ->max('shift') ?? 0;
        $shift_number = $cashier + 1;
        $this->cashier_shift
        ->create([
            'shift' => $shift_number,
            'start_time' => now(),
            'cashier_man_id' => $request->user()->id,
            'cashier_id' => $request->cashier_id,
        ]);
        $request->user()->shift_number = $shift_number;
        $request->user()->save();

        return response()->json([
            'success' => 'You open shift success'
        ]);
    }

    public function end_shift(Request $request){
        $this->cashier_shift
        ->where('shift', $request->user()->shift_number)
        ->where('cashier_man_id', $request->user()->id)
        ->update([
            'end_time' => now()
        ]);
        $request->user()->shift_number = null;
        $request->user()->save();

        return response()->json([
            'success' => 'You close shift success'
        ]);
    }
    
    public function login(LoginRequest $request){
        // https://bcknd.food2go.online/api/user/auth/login
        // Keys
        // email, password
        // _______________________________________________________________________
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
        if (empty($sms_subscription) || $msg_number->balance <= 0) {
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
        // _______________________________________________________________________
        $user = $this->delivery
        ->where('email', $request->email)
        ->orWhere('phone', $request->email)
        ->first();
        $role = 'delivery';
        if (empty($user)) {
            $user = $this->user
            ->where('email', $request->email)
            ->where('deleted_at', 0)
            ->orWhere('phone', $request->email)
            ->where('deleted_at', 0)
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
                'errors' => 'This user does not have the ability to login'
            ], 405);
        }
        if ($user->status == 0) {
            return response()->json([
                'falid' => 'user is banned'
            ], 400);
        }
        if($user->signup_pos){
            return response()->json([
                'complete_signup' => 'You must complete proccessing of signup'
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
            
            $order = $this->orders 
            ->where('user_id', $user->id)
            ->where('order_status', 'delivered') 
            ->whereNull('rate') 
            ->orderByDesc("id")
            ->first();
            $rate = false;
            if($order && empty($order->rate) && !$order->is_cancel_evaluate){
                $rate = true;
            }
            $show_map = $this->company_info
            ->first()
            ?->show_map ?? 1;

            return response()->json([
                'user' => $user,
                'token' => $user->token,
                'addresses' => $addresses,
                'zones' => $zones,
                'show_map' => $show_map,
                'rate' => $rate,
                'order_id' => $rate ? $order?->id : null,
            ], 200);
        }
        else { 
            return response()->json(['errors'=>'creational not Valid'],403);
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
