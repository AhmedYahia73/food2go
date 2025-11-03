<?php

namespace App\Http\Controllers\api\cashier\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;

use App\Models\Order;
use App\Models\Setting;

class OrderController extends Controller
{
    public function __construct(private Order $orders,
    private Setting $settings){}

    public function pos_orders(Request $request){
        $order_recentage = $this->settings
        ->where("name", "order_precentage")
        ->first()?->setting ?? 100; 
        $orders = $this->orders
        ->select('id', 'order_number', 'created_at', 'sechedule_slot_id', 'admin_id', 'user_id', 'branch_id', 'amount', 'operation_status'
        ,'order_status', 'order_type',
        'delivery_id', 'address_id', 'source',
        'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 1)
        ->where(function($query){
            $query->where("take_away_status", "pick_up")
            ->where("order_type", "take_away")
            ->orWhere("delivery_status", "pick_up")
            ->where("order_type", "delivery")
            ->orWhere("order_type", "dine_in");

        })
        ->where("shift", $request->user()->shift_number) 
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->orderByDesc('id')
        ->with(['user:id,f_name,l_name,phone,image', 'branch:id,name', 'address' => function($query){
            $query->select('id', 'zone_id')
            ->with('zone:id,zone');
        }, 'admin:id,name,email,phone,image', 'payment_method:id,name,logo',
        'schedule:id,name', 'delivery'])
        ->get()
        ->map(function($item){
            return [ 
                'id' => $item->id,
                'order_number' => $item->order_number,
                'created_at' => $item->created_at,
                'amount' => $item->amount,
                'operation_status' => $item->operation_status,
                'order_type' => $item->order_type,
                'order_status' => $item->order_status,
                'source' => $item->source,
                'status' => $item->status,
                'points' => $item->points, 
                'rejected_reason' => $item->rejected_reason,
                'transaction_id' => $item->transaction_id,
                'user' => [
                    'f_name' => $item?->user?->f_name,
                    'l_name' => $item?->user?->l_name,
                    'phone' => $item?->user?->phone],
                'branch' => ['name' => $item?->branch?->name, ],
                'address' => ['zone' => ['zone' => $item?->address?->zone?->zone]],
                'admin' => ['name' => $item?->admin?->name,],
                'payment_method' => ['name' => $item?->payment_method?->name],
                'schedule' => ['name' => $item?->schedule?->name],
                'delivery' => ['name' => $item?->delivery?->name], 
            ];
        })->filter(function ($order, $index) use($order_recentage) {
            $positionInBlock = $index % 10;
            return $positionInBlock < ($order_recentage / 10);
        });
        $orders2 = $this->orders
        ->select('id', 'order_number', 'created_at', 'sechedule_slot_id', 'admin_id', 'user_id', 'branch_id', 'amount', 'operation_status'
        ,'order_status', 'order_type',
        'delivery_id', 'address_id', 'source',
        'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 1)
        ->where(function($query){
            $query->where("take_away_status", "!=", "pick_up")
            ->where("order_type", "take_away")
            ->orWhere("delivery_status", "!=", "pick_up")
            ->where("order_type", "delivery");

        })
        ->where("shift", $request->user()->shift_number) 
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->orderByDesc('id')
        ->with(['user:id,f_name,l_name,phone,image', 'branch:id,name', 'address' => function($query){
            $query->select('id', 'zone_id')
            ->with('zone:id,zone');
        }, 'admin:id,name,email,phone,image', 'payment_method:id,name,logo',
        'schedule:id,name', 'delivery'])
        ->get()
        ->map(function($item){
            return [ 
                'id' => $item->id,
                'order_number' => $item->order_number,
                'created_at' => $item->created_at,
                'amount' => $item->amount,
                'operation_status' => $item->operation_status,
                'order_type' => $item->order_type,
                'order_status' => $item->order_status,
                'source' => $item->source,
                'status' => $item->status,
                'points' => $item->points, 
                'rejected_reason' => $item->rejected_reason,
                'transaction_id' => $item->transaction_id,
                'user' => [
                    'f_name' => $item?->user?->f_name,
                    'l_name' => $item?->user?->l_name,
                    'phone' => $item?->user?->phone],
                'branch' => ['name' => $item?->branch?->name, ],
                'address' => ['zone' => ['zone' => $item?->address?->zone?->zone]],
                'admin' => ['name' => $item?->admin?->name,],
                'payment_method' => ['name' => $item?->payment_method?->name],
                'schedule' => ['name' => $item?->schedule?->name],
                'delivery' => ['name' => $item?->delivery?->name], 
            ];
        });
        $orders = $orders->merge($orders2);

        return response()->json([
            "orders" => $orders,
        ]);
    }

    public function online_orders(Request $request){
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
 
        } else {
            $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
            $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');
        } 
        $start = $start->subDay();

        $order_recentage = $this->settings
        ->where("name", "order_precentage")
        ->first()?->setting ?? 100; 
        $orders = $this->orders
        ->select('id', 'order_number', 'created_at', 'sechedule_slot_id', 'admin_id', 'user_id', 'branch_id', 'amount', 'operation_status'
        ,'order_status', 'order_type',
        'delivery_id', 'address_id', 'source',
        'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->where(function($query){
            $query->where("take_away_status", "pick_up")
            ->where("order_type", "take_away")
            ->orWhere("delivery_status", "pick_up")
            ->where("order_type", "delivery")
            ->orWhere("order_type", "dine_in");

        })
        ->whereBetween('created_at', [$start, $end])
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->where('order_status', 'delivered')
        ->orderByDesc('id')
        ->with(['user:id,f_name,l_name,phone,image', 'branch:id,name', 'address' => function($query){
            $query->select('id', 'zone_id')
            ->with('zone:id,zone');
        }, 'admin:id,name,email,phone,image', 'payment_method:id,name,logo',
        'schedule:id,name', 'delivery'])
        ->get()
        ->map(function($item){
            return [ 
                'id' => $item->id,
                'order_number' => $item->order_number,
                'created_at' => $item->created_at,
                'amount' => $item->amount,
                'operation_status' => $item->operation_status,
                'order_type' => $item->order_type,
                'order_status' => $item->order_status,
                'source' => $item->source,
                'status' => $item->status,
                'points' => $item->points, 
                'rejected_reason' => $item->rejected_reason,
                'transaction_id' => $item->transaction_id,
                'user' => [
                    'f_name' => $item?->user?->f_name,
                    'l_name' => $item?->user?->l_name,
                    'phone' => $item?->user?->phone],
                'branch' => ['name' => $item?->branch?->name, ],
                'address' => ['zone' => ['zone' => $item?->address?->zone?->zone]],
                'admin' => ['name' => $item?->admin?->name,],
                'payment_method' => ['name' => $item?->payment_method?->name],
                'schedule' => ['name' => $item?->schedule?->name],
                'delivery' => ['name' => $item?->delivery?->name], 
            ];
        })->filter(function ($order, $index) use($order_recentage) {
            $positionInBlock = $index % 10;
            return $positionInBlock < ($order_recentage / 10);
        });
        $orders2 = $this->orders
        ->select('id', 'order_number', 'created_at', 'sechedule_slot_id', 'admin_id', 'user_id', 'branch_id', 'amount', 'operation_status'
        ,'order_status', 'order_type',
        'delivery_id', 'address_id', 'source',
        'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 1)
        ->where(function($query){
            $query->where("take_away_status", "!=", "pick_up")
            ->where("order_type", "take_away")
            ->orWhere("delivery_status", "!=", "pick_up")
            ->where("order_type", "delivery");

        })
        ->whereBetween('created_at', [$start, $end]) 
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->orderByDesc('id')
        ->with(['user:id,f_name,l_name,phone,image', 'branch:id,name', 'address' => function($query){
            $query->select('id', 'zone_id')
            ->with('zone:id,zone');
        }, 'admin:id,name,email,phone,image', 'payment_method:id,name,logo',
        'schedule:id,name', 'delivery'])
        ->get()
        ->map(function($item){
            return [ 
                'id' => $item->id,
                'order_number' => $item->order_number,
                'created_at' => $item->created_at,
                'amount' => $item->amount,
                'operation_status' => $item->operation_status,
                'order_type' => $item->order_type,
                'order_status' => $item->order_status,
                'source' => $item->source,
                'status' => $item->status,
                'points' => $item->points, 
                'rejected_reason' => $item->rejected_reason,
                'transaction_id' => $item->transaction_id,
                'user' => [
                    'f_name' => $item?->user?->f_name,
                    'l_name' => $item?->user?->l_name,
                    'phone' => $item?->user?->phone],
                'branch' => ['name' => $item?->branch?->name, ],
                'address' => ['zone' => ['zone' => $item?->address?->zone?->zone]],
                'admin' => ['name' => $item?->admin?->name,],
                'payment_method' => ['name' => $item?->payment_method?->name],
                'schedule' => ['name' => $item?->schedule?->name],
                'delivery' => ['name' => $item?->delivery?->name], 
            ];
        });
        $orders = $orders->merge($orders2);

        return response()->json([
            "orders" => $orders,
        ]);
    }
}
