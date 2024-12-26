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
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
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
        ->select('id', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->whereNotNull('status')
        ->with(['user'])
        ->get();

        return response()->json([
            'orders' => $orders_details
        ]);
    }

    public function receipt($id){
        // https://bcknd.food2go.online/admin/payment/receipt/{id}
        $receipt = $this->orders
        ->select('receipt')
        ->where('id', $id)
        ->first();

        return response()->json([
            'receipt' => $receipt
        ]);
    }

    public function approve($id){
        // https://bcknd.food2go.online/admin/payment/approve/{id}
        $order = $this->orders
        ->where('id', $id)
        ->first();
        $user = auth()->user();
        $user->points += $order->points;
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
