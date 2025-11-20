<?php

namespace App\Http\Controllers\api\admin\deal_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use App\Models\Deal;
use App\Models\DealUser;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\FinantiolAcounting; 
use App\Models\TimeSittings; 

class DealOrderController extends Controller
{
    public function __construct(private Deal $deals, private DealUser $deal_user,
    private Order $orders, private OrderDetail $order_details, 
    private TimeSittings $TimeSittings){}

    public function deal_order(Request $request){
        // https://bcknd.food2go.online/admin/dealOrder
        // code
        $validator = Validator::make($request->all(), [
            'code' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $nowSubThreeMinutes = Carbon::now()->subMinutes(3);
        $code = $request->code;
        try {
            $deals = $this->deals
            ->whereHas('deal_customer', function($query) use ($nowSubThreeMinutes, $code){
                $query->where('deal_user.ref_number', $code)
                ->where('deal_user.created_at', '>=', $nowSubThreeMinutes)
                ->where('deal_user.status', 0);
            })
            ->with(['deal_customer' => function($query) use ($nowSubThreeMinutes, $code){
                $query->where('deal_user.ref_number', $code)
                ->where('deal_user.created_at', '>=', $nowSubThreeMinutes)
                ->where('deal_user.status', 0)
                ->first();
            }])
            ->first();
            if (!empty($deals)) { 
                return response()->json([
                    'deal' => $deals,
                    'user' => $deals->deal_customer[0],
                ]);
            } else {
                return response()->json([
                    'faild' => 'Code is expired'
                ], 200);
            }
        } catch (QueryException $q) {
            return response()->json([
                'faild' => 'Code is expired'
            ], 200);
        }
 
    }
 
    public function add(Request $request){
        // https://bcknd.food2go.online/admin/dealOrder/add
        // Keys
        // deal_id, user_id, paid_by[card, cash]
        $validator = Validator::make($request->all(), [
            'deal_id' => 'required|exists:deals,id',
            'user_id' => 'required|exists:users,id',
            'financials' => 'required|array',
            'financials.*.id' => 'required|exists:finantiol_acountings,id',
            'financials.*.amount' => 'required|numeric',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $deals = $this->deal_user
        ->where('deal_id', $request->deal_id)
        ->where('user_id', $request->user_id) 
        ->first();
        // $deals->deal_customer[0]->pivot->status = 1; 
        // $deals->save();
        // return $deals;
        if(empty($deals)){
            return response()->json([
                "errors" => "user not make this deal"
            ], 400);
        }
        $deals->status = 1;
        $deals->save();
        foreach ($request->financials as $item) {
            $deals->financials()->attach($item["id"], ["amount" => $item['amount']]);

            $financial = FinantiolAcounting::
            where("id", $item['id'])
            ->first();
            if($financial){
                $financial->balance += $item['amount'];
                $financial->save();
            }
            
        }

        return response()->json([
            'success' => 'You record order success'
        ]);
    }

    public function orders(){
      $time_sittings = $this->TimeSittings 
        ->get();
        if ($time_sittings->count() > 0) {
            $from = $time_sittings[0]->from;
            $end = date('Y-m-d') . ' ' . $time_sittings[$time_sittings->count() - 1]->from;
            $hours = $time_sittings[$time_sittings->count() - 1]->hours;
            $minutes = $time_sittings[$time_sittings->count() - 1]->minutes;
            $from = date('Y-m-d') . ' ' . $from;
            $start = Carbon::parse($from);
            $end = Carbon::parse($end);
			$end = Carbon::parse($end)->addHours($hours)->addMinutes($minutes);
            if ($start >= $end) {
                $end = $end->addDay();
            }
			if($start >= now()){
                $start = $start->subDay();
			}

            // if ($start > $end) {
            //     $end = Carbon::parse($from)->addHours($hours)->subDay();
            // }
            // else{
            //     $end = Carbon::parse($from)->addHours(intval($hours));
            // } format('Y-m-d H:i:s')
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        } 
        $start = $start->subDay();
        $orders = $this->deal_user
        ->with(["user", "deal", "financials:id,name"])
        ->whereBetween('created_at', [$start, $end])
        ->get()
        ->map(function($item){
            return [
                "id" => $item->id,
                "order_status" => $item->order_status,
                "user_name" => $item?->user?->name,
                "user_phone" => $item?->user?->phone,
                "user_phone_2" => $item?->user?->phone_2,
                "deal_name" => $item->deal?->title,
                "deal_description" => $item->deal?->description,
                "deal_image" => $item->deal?->image_link,
                "deal_price" => $item->deal?->price,
            ];
        });

        return response()->json([
            "orders" => $orders
        ]);
    }

    public function order_status(Request $request, $id){
         $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:preparing,preparation,done,pick_up',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $orders = $this->deal_user
        ->where("id", $id)
        ->update([
            'order_status' => $request->order_status
        ]);

        return response()->json([
            "success" => "You update data success"
        ]);
    }
}
