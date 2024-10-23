<?php

namespace App\Http\Controllers\api\admin\deal_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;

use App\Models\Deal;
use App\Models\DealUser;

class DealOrderController extends Controller
{
    public function __construct(private Deal $deals, private DealUser $deal_user){}

    public function deal_order(){
        // https://backend.food2go.pro/admin/dealOrder
        $deals = $this->deals
        ->with('deal_customer')
        ->whereHas('deal_customer')
        ->get();

        return response()->json([
            'deals' => $deals
        ]);
    }
 
    public function status(Request $request){
        // https://backend.food2go.pro/admin/dealOrder/status
        // Keys
        // pivot_id
        $validator = Validator::make($request->all(), [
            'pivot_id' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $this->deal_user->where('id', $request->pivot_id)
        ->update([
            'status' => 1
        ]);

        return response()->json([
            'success' => 'You record order as active success'
        ]);
    }
}
