<?php

namespace App\Http\Controllers\api\customer\deal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\DealResource;

use App\Models\Deal;
use App\Models\DealTimes;

class DealController extends Controller
{
    public function __construct(private Deal $deals, private DealTimes $deal_times){}

    public function index(Request $request){
        // https://bcknd.food2go.online/customer/deal
        $locale = $request->locale ?? $request->query('locale', app()->getLocale()); // Get Local Translation
        $today = Carbon::now()->format('l');
        $deals = $this->deals
        ->with('times')
        ->where('daily', 1)
        ->where('status', 1)
        ->where('start_date', '<=', date('Y-m-d'))
        ->where('end_date', '>=', date('Y-m-d'))
        ->orWhere('status', 1)
        ->where('start_date', '<=', date('Y-m-d'))
        ->where('end_date', '>=', date('Y-m-d'))
        ->whereHas('times', function($query) use($today) {
            $query->where('day', $today)
            ->where('from', '<=', now()->format('H:i:s'))
            ->where('to', '>=', now()->format('H:i:s'));
        })
        ->withLocale($locale)
        ->get();
        $deals = DealResource::collection($deals);
        
        return response()->json([
            'deals' => $deals,
        ]);
    }
 
    public function order(Request $request){
        // https://bcknd.food2go.online/customer/deal/order
        // Keys
        // deal_id
        $validator = Validator::make($request->all(), [
            'deal_id' => 'required|exists:deals,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'error' => $validator->errors(),
            ],400);
        }
        $user = $request->user();
        $ref_number = rand(100000 , 999999);
        $data = $user->deals->where('pivot.ref_number', $ref_number);
        while (count($data) > 0) {
            $ref_number = rand(100000 , 999999);
            $data = $user->deals->where('pivot.ref_number', $ref_number);
        }
        $user->deals()->attach($request->deal_id, ['ref_number' => $ref_number,
        'created_at' => now()]);

        return response()->json([
            'ref_number' => $ref_number,
        ]);
    }

}
