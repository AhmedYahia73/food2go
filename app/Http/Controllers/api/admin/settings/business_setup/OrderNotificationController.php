<?php

namespace App\Http\Controllers\api\admin\settings\business_setup;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\OrderNotification;

class OrderNotificationController extends Controller
{
    public function __construct(private OrderNotification $order_notification){}

    public function view(){
        $order_notification = $this->order_notification
        ->get();

        return response()->json([
            'order_notification' => $order_notification,
        ]);
    }

    public function create(Request $request){
        // admin/settings/business_setup/order_delay_notification/add
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'unique:order_notifications,email'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $userRequest = $validator->validated();
        $order_notification = $this->order_notification
        ->create($userRequest);

        return response()->json([
            'order_notification' => $order_notification,
        ]);
    }

    public function modify(Request $request, $id){
        // admin/settings/business_setup/order_delay_notification/update/{id}
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'unique:order_notifications,email,' . $id], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $userRequest = $validator->validated();
        $order_notification = $this->order_notification
        ->where('id', $id)
        ->first();
        if (empty($order_notification)) {
            return response()->json([
                'errors' => 'id not found',
            ], 400);
        }
        $order_notification->update($userRequest);

        return response()->json([
            'order_notification' => $order_notification,
        ]);
    }

    public function delete($id){
        // admin/settings/business_setup/order_delay_notification/delete/{id}
        $this->order_notification
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success',
        ]);
    }
}
