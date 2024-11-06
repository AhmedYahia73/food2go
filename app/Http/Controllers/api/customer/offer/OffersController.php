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
use App\Models\OfferOrder;

class OffersController extends Controller
{
    public function __construct(private Offer $offers, private User $user, private Order $orders,
    private OfferOrder $offer_order){}

    public function offers(){
        // https://backend.food2go.pro/customer/offers
        $offers = $this->offers->get();

        return response()->json([
            'offers' => $offers
        ]);
    }

    public function buy_offer(Request $request){
        // https://backend.food2go.pro/customer/offers/buy_offer
        // Keys
        // address, offer_id, date, order_type
        $validator = Validator::make($request->all(), [
            'offer_id' => 'required|exists:offers,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        } 
        $ref_number = rand(100000 , 999999);
        $data = $this->offer_order
        ->where('code', $ref_number)
        ->first();
        while (!empty($data)) {
            $ref_number = rand(100000 , 999999);
            $data = $this->offer_order
            ->where('code', $ref_number)
            ->first();
        }

        $order = $this->offer_order
        ->create([
            'date' => now(),
            'user_id' => $request->user()->id,
            'code' => $code,
            'offer_id' => $request->offer_id,
        ]);
        // $order->offers()->attach($request->offer_id);

        return response()->json([
            'ref_number' => $ref_number
        ]);
    }
}
