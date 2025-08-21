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

class WaiterCallController extends Controller
{
    public function __construct(
    private NewNotification $notification, private DeviceToken $device_token,
    private CallWaiter $call_waiter, private CafeTable $cafe_table){}
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
        $device_token = $this->device_token
        ->get()
        ?->pluck('token')
        ?->toArray();
        $this->sendNotificationToMany($device_token, $cafe_table->table_number, $body);
        
        return response()->json([
            'success' => 'You call waiter success'
        ]);
    }
}
