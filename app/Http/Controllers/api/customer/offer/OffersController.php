<?php

namespace App\Http\Controllers\api\customer\offer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use stdClass;

use App\Models\Offer;
use App\Models\Order;
use App\Models\User;

class OffersController extends Controller
{
    public function __construct(private Offer $offers, private User $user, private Order $orders){}

    public function offers(){
        $offers = $this->offers->get();

        return response()->json([
            'offers' => $offers
        ]);
    }

    public function buy_offer(Request $request){
        // Keys
        // address, offer_id, date, order_type
        $validator = Validator::make($request->all(), [
            'offer_id' => 'required|exists:offers,id',
            'date' => 'date',
            'order_type' => 'in:delivery,take_away,dine_in'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $offer = $this->offers
        ->where('id', $request->offer_id)
        ->first();
        $user = $request->user();
        $user->points = $user->points - $offer->points;
        if ($request->address) {
            $address = json_decode($user->address) ?? new stdClass();
            $address->{$request->address} = $request->address;
            $user->address = json_encode($address);
        }
        $user->save();
        $order = $this->orders
        ->create([
            'date' => $request->date,
            'user_id' => $user->id,
            'amount' => 0,
            'order_status' => 'pending',
            'order_type' => $request->order_type,
            'paid_by' => 'points'
        ]);
        $order->offers()->attach($request->offer_id);

        return response()->json([
            'success' => 'You buy offer success'
        ]);
    }
}
