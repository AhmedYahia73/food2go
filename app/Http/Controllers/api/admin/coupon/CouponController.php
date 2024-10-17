<?php

namespace App\Http\Controllers\api\admin\coupon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Coupon;

class CouponController extends Controller
{
    public function __construct(private Coupon $coupons){}

    public function view(){
        $coupons = $this->coupons
        ->with('products')
        ->get();

        return response()->json([
            'coupons' => $coupons
        ]);
    }

    public function status(Request $request, $id){
        // Keys
        // status
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }

        $this->coupons->where('id', $id)
        ->update([
            'status' => $request->status
        ]);

        if ($request->status == 0) {
            return response()->json([
                'success' => 'banned'
            ]);
        } else {
            return response()->json([
                'success' => 'active'
            ]);
        }
    }
}
