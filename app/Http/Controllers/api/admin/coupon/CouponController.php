<?php

namespace App\Http\Controllers\api\admin\coupon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Coupon;

class CouponController extends Controller
{
    public function __construct(private Coupon $coupons){}

    public function view(){

    }

    public function status(Request $request, $id){

    }
}
