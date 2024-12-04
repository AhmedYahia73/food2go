<?php

namespace App\Http\Controllers\api\admin\payments;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Order;

class PaymentController extends Controller
{
    public function __construct(private Order $orders){}

    public function pending(){
        // https://bcknd.food2go.online/admin/payment/pending
        $orders_details = $this->orders
        ->whereNull('status')
        ->with('user')
        ->get();

        return response()->json([
            'orders' => $orders_details
        ]);
    }

    public function history(){
        // https://bcknd.food2go.online/admin/payment/history
        $orders_details = $this->orders
        ->whereNotNull('status')
        ->with(['user'])
        ->get();

        return response()->json([
            'orders' => $orders_details
        ]);
    }

    public function approve($id){
        // https://bcknd.food2go.online/admin/payment/approve/{id}
        $order = $this->orders
        ->where('id', $id)
        ->first();
        
        $order->update([
            'status' => 1
        ]);

        return response()->json([
            'success' => 'You approve payment success'
        ]);
    }

    public function rejected(Request $request, $id){
        // https://bcknd.food2go.online/admin/payment/rejected/{id}
        // Keys
        // rejected_reason
        $validator = Validator::make($request->all(), [
            'rejected_reason' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->orders
        ->where('id', $id)
        ->update([
            'status' => 0,
            'rejected_reason' => $request->rejected_reason
        ]);

        return response()->json([
            'success' => 'You reject payment success'
        ]);
    }
}
