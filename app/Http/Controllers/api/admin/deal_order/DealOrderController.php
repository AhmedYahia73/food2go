<?php

namespace App\Http\Controllers\api\admin\deal_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\Deal;
use App\Models\DealUser;
use App\Models\Order;
use App\Models\OrderDetail;

class DealOrderController extends Controller
{
    public function __construct(private Deal $deals, private DealUser $deal_user,
    private Order $orders, private OrderDetail $order_details){}

    public function deal_order(Request $request){
        // https://bcknd.food2go.online/admin/dealOrder
        // code
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $nowSubThreeMinutes = Carbon::now()->subMinutes(3);
        $code = $request->code;
        try {
            $deals = $this->deals
            ->whereHas('deal_customer', function($query) use ($nowSubThreeMinutes, $code){
                $query->where('deal_user.ref_number', $code)
                ->where('deal_user.created_at', '>=', $nowSubThreeMinutes);
            })
            ->with(['deal_customer' => function($query) use ($nowSubThreeMinutes, $code){
                $query->where('deal_user.ref_number', $code)
                ->where('deal_user.created_at', '>=', $nowSubThreeMinutes)
                ->first();
            }])
            ->first();
            if (!empty($deals)) { 
                return response()->json([
                    'deal' => $deals
                ]);
            } else {
                return response()->json([
                    'faild' => 'Code is expired'
                ], 400);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'faild' => 'Code is expired'
            ], 400);
        }
 
    }
 
    public function add(Request $request){
        // https://bcknd.food2go.online/admin/dealOrder/add
        // Keys
        // deal_id, user_id, paid_by[card, cash]
        $validator = Validator::make($request->all(), [
            'deal_id' => 'required|exists:deals,id',
            'user_id' => 'required|exists:users,id',
            'paid_by' => 'required'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $deals = $this->deals
        ->where('id', $request->deal_id)
        ->first();
        $order = $this->orders
        ->create([
            'date' => now(),
            'user_id' => $request->user_id,
            'amount' => $deals->price,
            'order_status' => 'delivered',
            'order_type' => 'application',
            'payment_status' => 'paid',
            'paid_by' => $request->paid_by,
        ]);
        $order_detail = $this->order_details
        ->create([
            'order_id' => $order->id,
            'count' => 1,
            'deal_id' => $request->deal_id
        ]);

        return response()->json([
            'success' => 'You record order success'
        ]);
    }
}
