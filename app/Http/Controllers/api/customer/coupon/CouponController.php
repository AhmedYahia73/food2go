<?php

namespace App\Http\Controllers\api\customer\coupon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\coupon\CouponRequest;

use App\Models\Coupon;
use App\Models\Order;

class CouponController extends Controller
{
    public function __construct(private Coupon $coupon, private Order $orders){}

    public function coupon(CouponRequest $request){
        // https://bcknd.food2go.online/customer/coupon
        // Keys
        // coupon, 
        // product[{product_id, total}]
        $coupon = $this->coupon
        ->where('start_date', '<=', date('Y-m-d'))
        ->where('expire_date', '>=', date('Y-m-d'))
        ->where('code', $request->coupon)
        ->first();
        if (empty($coupon)) {
            return response()->json([
                'faild' => 'Coupon is expired'
            ], 400);
        }

        if ($coupon->type == 'first_order') {
            $orders = $this->orders
            ->where('user_id', $request->user()->id)
            ->first();
            if (!empty($orders)) {
                return response()->json([
                    'faild' => 'Order must be first'
                ],400);
            }
        }
        
        $total = 0;
        $products = is_string($request->product) ? json_decode($request->product) : $request->product;
        $products = collect($products);
        $discount = 0;

        if ($coupon->product == 'all') {
            $total = $products->sum('total');
        }
        else{
            $coupon_products = $coupon->products->pluck('id');
            $products_ids = $products->pluck('product_id');
            foreach ($products as $product) {
                if ($coupon_products->contains($product->product_id)) {
                    $total += $product->total;
                }
            }
        }

        if ($coupon->min_purchase > $total) {
            return response()->json([
                'faild' => 'Products that supported by coupon must price bigger than ' . $total
            ], 400);
        }

        $user = $request->user();
        $user_coupon = $user->coupons
        ->where('code', $request->coupon)->count();
        if ($coupon->number_usage_status == 'fixed' && $coupon->number_usage <= 0) {
            return response()->json([
                'faild' => 'Coupon is over'
            ], 400);
        }
        
        if ($coupon->number_usage_user_status == 'fixed' && $coupon->number_usage_user <= $user_coupon) {
            return response()->json([
                'faild' => 'You used maximum number of times'
            ], 400);
        }

        if ($coupon->discount_type == 'percentage') {
            $discount = $total * $coupon->discount / 100;
        } 
        else {
            $discount = $coupon->discount;
        }

        if ($coupon->max_discount_status && $discount > $coupon->max_discount) {
            $discount = $coupon->max_discount;
        }
        

        return response()->json([
            'discount' => $discount
        ]);
    }
}
