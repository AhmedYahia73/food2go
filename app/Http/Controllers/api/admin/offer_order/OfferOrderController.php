<?php

namespace App\Http\Controllers\api\admin\offer_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

use App\Models\OfferOrder;

class OfferOrderController extends Controller
{
    public function __construct(private OfferOrder $offer_order){}

    public function check_order(Request $request){
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $nowPlusThreeMinutes = Carbon::now()->addMinutes(3);
        $offer_order = $this->offer_order
        ->where('date', '>=', $offer_order)
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
}
