<?php

namespace App\Http\Controllers\api\client\waiter_call;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\trait\Notifications; 

use App\Models\NewNotification; 
use App\Models\DeviceToken;
use App\Models\CafeTable;
use App\Models\CallWaiter;
use App\Models\CashierMan;
use App\Models\CaptainOrder; 
use App\Models\Waiter; 

class WaiterCallController extends Controller
{
    public function __construct(
    private NewNotification $notification, private DeviceToken $device_token,
    private CallWaiter $call_waiter, private CafeTable $cafe_table,
    private CashierMan $cashier_man, private CaptainOrder $captain_order,
    private Waiter $waiter){}
    use Notifications;

    public function call_waiter(Request $request){
        $validator = Validator::make($request->all(), [
            'table_id' => ['required', 'exists:cafe_tables,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $call_waiter = $this->call_waiter
        ->create([
            'table_id' => $request->table_id
        ]);
        $cafe_table = $this->cafe_table
        ->where('id', $request->table_id)
        ->with('location:id,name')
        ->first();
        $body = 'Table ' . $cafe_table->table_number . 
            ' at location ' . $cafe_table?->location?->name . ' Call Waiter';
        $notification = $this->notification
        ->create([
            'title' => $cafe_table->table_number,
            'notification' => $body, 
        ]);
        // $device_token = $this->device_token
        // ->get()
        // ?->pluck('token')
        // ?->toArray();
        $users_tokens1 = $this->waiter
        ->where("branch_id", $cafe_table->branch_id)
        ->get()
        ?->pluck("fcm_token");
        $users_tokens2 = $this->captain_order
        ->where("branch_id", $cafe_table->branch_id)
        ->where("waiter", 1)
        ->pluck('fcm_token');
        $device_token = $users_tokens1->merge($users_tokens2);
        $device_token = $device_token->toArray();
        $body = 'Table ' . $cafe_table->table_number . 
            ' at location ' . $cafe_table?->location?->name . ' Want Waiter';
        $this->sendNotificationToMany($device_token, $cafe_table->table_number, $body);
        
        return response()->json([
            'success' => 'You call waiter success'
        ]);
    }

    public function call_payment(Request $request){
        $validator = Validator::make($request->all(), [
            'table_id' => ['required', 'exists:cafe_tables,id'],
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $cafe_table = $this->cafe_table
        ->where('id', $request->table_id)
        ->with('location:id,name')
        ->first();
        $branch_id = $cafe_table->branch_id;
        $users_tokens1 = $this->cashier_man
        ->where("branch_id", $branch_id)
        ->pluck('fcm_token');
        $users_tokens2 = $this->captain_order
        ->where("branch_id", $branch_id)
        ->pluck('fcm_token');
        $device_token = $this->device_token
        ->whereNotNull("admin_id")
        ->get()
        ?->pluck('token');
        $users_tokens = $users_tokens1->merge($users_tokens2, $device_token)
        ->filter()->toArray();
        $body = 'Table ' . $cafe_table->table_number . 
            ' at location ' . $cafe_table?->location?->name . ' Want To Pay';
  
        $notifications = $this->sendNotificationToMany($users_tokens, $cafe_table->table_number, $body);
        
        return response()->json([
            'success' => 'You call Pay success',
            "notifications" => $notifications->count()
        ]);
    }
}
