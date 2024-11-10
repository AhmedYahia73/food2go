<?php

namespace App\Http\Controllers\api\admin\coupon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\admin\coupon\CouponRequest;

use App\Models\Coupon;

class CreateCouponController extends Controller
{
    public function __construct(private Coupon $coupons){}
    protected $couponRequest = [
        'type',
        'title',
        'code',
        'start_date',
        'expire_date',
        'min_purchase',
        'max_discount_status',
        'max_discount',
        'product',
        'number_usage_status',
        'number_usage',
        'number_usage_user_status',
        'number_usage_user',
        'discount_type',
        'discount',
        'status',
    ];

    public function create(CouponRequest $request){
        // https://bcknd.food2go.online/admin/coupon/add
        // Keys
        // type ['first_order', 'normal'], title, code, start_date, expire_date, min_purchase
        // max_discount_status, max_discount, product['all', 'selected'], 
        //number_usage_status['fixed', 'unlimited'], number_usage, 
        // number_usage_user_status['fixed', 'unlimited']
        // number_usage_user, discount_type['value', 'percentage'], discount, status
        // products_id[]
        $couponRequest = $request->only($this->couponRequest);
        $coupon = $this->coupons->create($couponRequest);
        if ($request->products_id) {
            $coupon->products()->attach($request->products_id);
        }

        return response()->json([
            'success' => 'You add data success'
        ]);
    }

    public function modify(CouponRequest $request, $id){
        // https://bcknd.food2go.online/admin/coupon/update/{id}
        // Keys
        // type ['first_order', 'normal'], title, code, start_date, expire_date, min_purchase
        // max_discount_status, max_discount, product['all', 'selected'], 
        //number_usage_status['fixed', 'unlimited'], number_usage, 
        // number_usage_user_status['fixed', 'unlimited']
        // number_usage_user, discount_type['value', 'percentage'], discount, status
        // products_id[]
        $couponRequest = $request->only($this->couponRequest);
        $coupon = $this->coupons
        ->where('id', $id)
        ->first();
        $coupon->type = $request->type ?? null;
        $coupon->title = $request->title ?? null;
        $coupon->code = $request->code ?? null;
        $coupon->start_date = $request->start_date ?? null;
        $coupon->expire_date = $request->expire_date ?? null;
        $coupon->min_purchase = $request->min_purchase ?? 0;
        $coupon->max_discount_status = $request->max_discount_status ?? null;
        $coupon->max_discount = $request->max_discount ?? null;
        $coupon->product = $request->product ?? null;
        $coupon->number_usage_status = $request->number_usage_status ?? null;
        $coupon->number_usage = $request->number_usage ?? null;
        $coupon->number_usage_user_status = $request->number_usage_user_status ?? null;
        $coupon->number_usage_user = $request->number_usage_user ?? null;
        $coupon->discount_type = $request->discount_type ?? null;
        $coupon->discount = $request->discount ?? null;
        $coupon->status = $request->status ?? null;
        $coupon->save();
        $coupon->products()->sync($request->products_id);

        return response()->json([
            'success' => 'You update data success'
        ]);
    }

    public function delete($id){
        // https://bcknd.food2go.online/admin/coupon/delete/{id}
        $coupon = $this->coupons
        ->where('id', $id)
        ->delete();

        return response()->json([
            'success' => 'You delete data success'
        ]);
    }
}
