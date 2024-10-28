<?php

namespace App\Http\Controllers\api\customer\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\order\OrderRequest;

use App\Models\Order;
use App\Models\OrderDetails;

class MakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetails $order_details){}
    protected $orderRequest = [
        'date',
        'branch_id',
        'amount',
        'payment_status',
        'total_tax',
        'total_discount',
        'address',
        'order_type',
        'paid_by',
    ];

    public function order(OrderRequest $request){
        // Keys
        // date, branch_id, amount, payment_status, total_tax, total_discount, address
        // order_type, paid_by
        $orderRequest = $request->only($this->orderRequest);
        $user = $request->user();
        $orderRequest['user_id'] = $user->id;
        $orderRequest['order_status'] = 'pending';
        $order = $this->order
        ->create($orderRequest);        
        $user->address = $request->address && is_string($request->address) 
        ? $request->address : json_encode($request->address);
        $user->save();
    }
}
