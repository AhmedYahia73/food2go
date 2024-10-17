<?php

namespace App\Http\Controllers\api\admin\coupon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\coupon\CouponRequest;

use App\Models\Coupon;

class CreateCouponController extends Controller
{
    public function __construct(private Coupon $coupons){}

    public function create(CouponRequest $request){

    }

    public function modify(CouponRequest $request, $id){

    }

    public function delete($id){

    }
}
