<?php

namespace App\Http\Controllers\api\admin\offer_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\OfferOrder;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;

class OfferOrderController extends Controller
{
    public function __construct(private OfferOrder $offer_order, private Offer $offer,
    private Order $order, private User $user){}

    public function check_order(Request $request){
        // https://backend.food2go.pro/admin/offerOrder
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $nowPlusThreeMinutes = Carbon::now()->subMinutes(3);
        $offer_order = $this->offer_order
        ->where('date', '>=', $nowPlusThreeMinutes)
        ->where('code', $request->code)
        ->first();

        if (empty($offer_order)) {
            return response()->json([
                'faild' => 'Code is expired'
            ], 400);
        } 
        else {
            return response()->json([
                'offer' => $offer_order->offer
            ]);
        }
    }

    public function approve_offer(Request $request){
        // https://backend.food2go.pro/admin/offerOrder/approve_offer
        // Keys
        // offer_order_id 
        $validator = Validator::make($request->all(), [
            'offer_order_id' => 'required|exists:offer_orders,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $offer_order = $this->offer_order
        ->where('id', $request->offer_order_id)
        ->first();

        $user = $this->user
        ->where('id', $offer_order->user_id )
        ->first();
        if ($user->points < $offer_order->offer->points) {
            return response()->json([
                'faild' => 'Your points is not enough'
            ], 400);
        }
        $user->points = $user->points - $offer_order->offer->points; //
        $order = $this->order
        ->create([
            'date' => now(),
            'user_id' => $user->id,
            'amount' => 0,
            'order_status' => 'delivered',
            'paid_by' => 'points'
        ]);
        $order->offers()->attach($offer_order->offer->id);

        return response()->json([
            'success' => 'You confirm offer success'
        ]);
    }
}
