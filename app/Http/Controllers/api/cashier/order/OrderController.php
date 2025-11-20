<?php

namespace App\Http\Controllers\api\cashier\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\trait\Recipe;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator; 
use Illuminate\Support\Collection;
use App\Http\Requests\cashier\UpdateOrderRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\CancelOrderMail;
use App\trait\POS;

use App\Models\Order;
use App\Models\OrderFinancial;
use App\Models\Setting;
use App\Models\TimeSittings;
use App\Models\Delivery;
use App\Models\FinantiolAcounting;
use App\Models\OrderDetail;
use App\Models\Branch;
use App\Models\Product;
use App\Models\ExcludeProduct;
use App\Models\Addon;
use App\Models\ExtraProduct;
use App\Models\VariationProduct;
use App\Models\OptionProduct;

class OrderController extends Controller
{
    public function __construct(private Order $orders,
    private Setting $settings, private TimeSittings $TimeSittings,
    private Delivery $deliveries, private Branch $branches,
    private OrderFinancial $order_financial, private OrderDetail $order_details,
    private FinantiolAcounting $financial_account, private Product $products,
    private ExcludeProduct $excludes, private Addon $addons,
    private ExtraProduct $extras, private VariationProduct $variation,
    private OptionProduct $options){}
    use Recipe;
    use POS;

    public function pos_orders(Request $request){
        $validator = Validator::make($request->all(), [
            'password' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $password = $this->settings
        ->where('name', 'password')
        ->first()?->setting ?? null;
        if($request->password == $password){
            $order_recentage = $this->settings
            ->where("name", "order_precentage")
            ->first()?->setting ?? 100;
            $order_recentage = intval($order_recentage);
            $orders = $this->orders
            ->where(function($query){
                $query->where('pos', 1)
                ->orWhere('pos', 0)
                ->where('order_status', '!=', 'pending');
            })
            ->where(function($query){
                $query->where("take_away_status", "pick_up")
                ->where("order_type", "take_away")
                ->orWhere("delivery_status", "done")
                ->where("order_type", "delivery")
                ->orWhere("order_type", "dine_in");

            })
            ->where("shift", $request->user()->shift_number) 
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            }) 
            ->where('order_active', 1)
            ->orderByDesc('id')
            ->with(['user:id,f_name,l_name,phone,image', 'branch:id,name', 'address' => function($query){
                $query->select('id', 'zone_id')
                ->with('zone:id,zone');
            }, 'admin:id,name,email,phone,image', 'payment_method:id,name,logo',
            'schedule:id,name', 'delivery', 'financial_accountigs:id,name'])
            ->get()
            ->map(function($item){
                $order_type = "";
                if ($item->order_type == "dine_in") {
                    $order_type = "pickup";
                }
                elseif ($item->order_type == "take_away") {
                    $order_type = $item->take_away_status;
                }
                elseif ($item->order_type == "delivery") {
                    $order_type = $item->delivery_status;
                }
                return [ 
                    'id' => $item->id,
                    'order_number' => $item->order_number,
                    'created_at' => $item->created_at,
                    'amount' => $item->amount,
                    'operation_status' => $item->operation_status,
                    'order_type' => $item->order_type,
                    'order_status' => $order_type,
                    'type' => $item->pos ? 'Point of Sale' : "Online Order",
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
                    'payment_method' => ['id' => $item?->payment_method?->id,
                                        'name' => $item?->payment_method?->name],
                    'financial_accountigs' => $item->financial_accountigs,
                    'schedule' => ['name' => $item?->schedule?->name],
                    'delivery' => ['name' => $item?->delivery?->name], 
                ];
            })->filter(function ($order, $index) use($order_recentage) {
                $positionInBlock = $index % 10;
                return $positionInBlock < ($order_recentage / 10);
            });
            $orders2 = $this->orders
            ->where(function($query){
                $query->where('pos', 1)
                ->orWhere('pos', 0)
                ->where('order_status', '!=', 'pending');
            })
            ->where(function($query){
                $query->where("take_away_status", "!=", "pick_up")
                ->where("order_type", "take_away")
                ->orWhere("delivery_status", "!=", "done")
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
            'schedule:id,name', 'delivery', 'financial_accountigs:id,name'])
            ->get()
            ->map(function($item){
                $order_type = "";
                if ($item->order_type == "dine_in") {
                    $order_type = "pickup";
                }
                elseif ($item->order_type == "take_away") {
                    $order_type = $item->take_away_status;
                }
                elseif ($item->order_type == "delivery") {
                    $order_type = $item->delivery_status;
                }
                return [ 
                    'id' => $item->id,
                    'order_number' => $item->order_number,
                    'created_at' => $item->created_at,
                    'amount' => $item->amount,
                    'operation_status' => $item->operation_status,
                    'order_type' => $item->order_type,
                    'type' => $item->pos ? 'Point of Sale' : "Online Order",
                    'order_status' => $order_type,
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
                    'payment_method' => ['id' => $item?->payment_method?->id,
                                        'name' => $item?->payment_method?->name],
                    'financial_accountigs' => $item->financial_accountigs,
                    'schedule' => ['name' => $item?->schedule?->name],
                    'delivery' => ['name' => $item?->delivery?->name], 
                ];
            });
            $orders = collect($orders);
            $orders2 = collect($orders2);

            $orders = $orders->merge($orders2)
            ->sortByDesc('id')
            ->values();
            $order_type = [
                "dine_in",
                "take_away",
                "delivery",
            ];
            return response()->json([
                "orders" => $orders,
                "order_type" => $order_type, 
            ]);
        }
        elseif(password_verify($request->input('password'), $request->user()->password) && $request->user()->real_orders){
           $order_recentage = $this->settings
            ->where("name", "order_precentage")
            ->first()?->setting ?? 100;
            $order_recentage = intval($order_recentage);
            $orders = $this->orders
            ->where(function($query){
                $query->where('pos', 1)
                ->orWhere('pos', 0)
                ->where('order_status', '!=', 'pending');
            })
            ->where("shift", $request->user()->shift_number) 
            ->where(function($query) {
                $query->where('status', 1)
                ->orWhereNull('status');
            }) 
            ->where('order_active', 1)
            ->orderByDesc('id')
            ->with(['user:id,f_name,l_name,phone,image', 'branch:id,name', 'address' => function($query){
                $query->select('id', 'zone_id')
                ->with('zone:id,zone');
            }, 'admin:id,name,email,phone,image', 'payment_method:id,name,logo',
            'schedule:id,name', 'delivery', 'financial_accountigs:id,name'])
            ->get()
            ->map(function($item){
                $order_type = "";
                if ($item->order_type == "dine_in") {
                    $order_type = "pickup";
                }
                elseif ($item->order_type == "take_away") {
                    $order_type = $item->take_away_status;
                }
                elseif ($item->order_type == "delivery") {
                    $order_type = $item->delivery_status;
                }
                return [ 
                    'id' => $item->id,
                    'order_number' => $item->order_number,
                    'created_at' => $item->created_at,
                    'amount' => $item->amount,
                    'operation_status' => $item->operation_status,
                    'order_type' => $item->order_type,
                    'order_status' => $order_type,
                    'type' => $item->pos ? 'Point of Sale' : "Online Order",
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
                    'payment_method' => ['id' => $item?->payment_method?->id,
                                        'name' => $item?->payment_method?->name],
                    'financial_accountigs' => $item->financial_accountigs,
                    'schedule' => ['name' => $item?->schedule?->name],
                    'delivery' => ['name' => $item?->delivery?->name], 
                ];
            });
            $order_type = [
                "dine_in",
                "take_away",
                "delivery",
            ];
            return response()->json([
                "orders" => $orders,
                "order_type" => $order_type, 
            ]);
        }

        return response()->json([
            "errors" => "password is wrong"
        ], 400);
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

        // $order_recentage = $this->settings
        // ->where("name", "order_precentage")
        // ->first()?->setting ?? 100; 
        $order_status = [
            "pending",
            "confirmed",
            "processing",
            "out_for_delivery",
            "delivered",
            "returned",
            "faild_to_deliver",
            "canceled",
            "scheduled",
            "refund",
        ];
        $orders = $this->orders
        ->select('id', 'order_number', 'created_at', 'sechedule_slot_id', 'admin_id', 'user_id', 'branch_id', 'amount', 'operation_status'
        ,'order_status', 'order_type',
        'delivery_id', 'address_id', 'source',
        'payment_method_id', 
        'status', 'points', 'rejected_reason', 'transaction_id')
        ->where('pos', 0)
        ->where('branch_id', $request->user()->branch_id)
        ->whereBetween('created_at', [$start, $end])
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->orderByDesc('id')
        ->where('order_status', 'pending')
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
                'paid' => !$item->payment_method_id == 2,
                'schedule' => ['name' => $item?->schedule?->name],
                'delivery' => ['name' => $item?->delivery?->name], 
            ];
        });
        // ->filter(function ($order, $index) use($order_recentage) {
        //     $positionInBlock = $index % 10;
        //     return $positionInBlock < ($order_recentage / 10);
        // }); 

        return response()->json([
            "orders" => $orders,
            "order_status" => $order_status,
        ]);
    }

    public function order_count(Request $request){
        
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
        $orders = $this->orders 
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
        $pending = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'pending')
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
        $confirmed = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'confirmed')
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
        $processing = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'processing')
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
        $out_for_delivery = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'out_for_delivery')
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
        $delivered = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'delivered')
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
        $returned = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'returned')
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
        $faild_to_deliver = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'faild_to_deliver')
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
        $canceled = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'canceled')
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
        $scheduled = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'scheduled')
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();
        $refund = $this->orders
        ->where('pos', 0)
        ->whereNull('captain_id')
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->where('order_status', 'refund')
        ->where("branch_id", $request->user()->id)
        ->whereBetween('created_at', [$start, $end])
        ->count();

        return response()->json([
            "orders" => $orders,
            "pending" => $pending,
            "confirmed" => $confirmed,
            "processing" => $processing,
            "out_for_delivery" => $out_for_delivery,
            "delivered" => $delivered,
            "returned" => $returned,
            "faild_to_deliver" => $faild_to_deliver,
            "canceled" => $canceled,
            "scheduled" => $scheduled,
            "refund" => $refund,
        ]);
    }

    public function order_item(Request $request, $id){
        $order = $this->orders
        ->select('id', 'receipt', 'date', 'user_id', 'branch_id', 'amount',
        'order_status', 'order_type', 'payment_status', 'total_tax', 'total_discount',
        'created_at', 'updated_at', 'pos', 'delivery_id', 'address_id', 'source',
        'notes', 'coupon_discount', 'order_number', 'payment_method_id', 'order_details',
        'status', 'points', 'rejected_reason', 'transaction_id', 'customer_cancel_reason', 
        'admin_cancel_reason', 'sechedule_slot_id', 'pos')
        ->with(['user:id,f_name,l_name,phone,phone_2,image,email', 
        'branch:id,name', 'delivery', 'payment_method:id,name,logo',
         'address.zone', 'admin:id,name,email,phone,image', 
        'schedule', 'financial_accountigs:id,name'])
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        })
        ->find($id);
        if(!$order){
            return response()->json([
                "errors" => "id wrong"
            ], 400);
        }
        $order->type = $order->pos ? 'Point of Sale' : 'Online Order';
        $order->makeHidden('order_details_data');
        $order_details = collect($order->order_details);
        foreach ($order_details as $item) {
        
            foreach ($item->product as $element) {
                $total = collect($item->variations)->pluck('options')->flatten(1)
                ->where('product_id', $element->product->id)->sum('price');
                $element->product->price += $total;
            }
        }
        $order->order_details = $order_details;
        try {
            $order->user->count_orders = $this->orders->where('user_id', $order->user_id)->count();
        } 
        catch (\Throwable $th) {
            $order->user = collect([]);
            $order->user->count_orders = 0;
        }
        if (!empty($order->branch)) {
            $order->branch->count_orders = $this->orders->where('branch_id', $order->branch_id)->count();
        }
        if (!empty($order->delivery_id)) {
            $order->delivery->count_orders = $this->orders
            ->where('delivery_id', $order->delivery_id)
            ->count();
        }
        $deliveries = $this->deliveries
        ->select('id', 'f_name', 'l_name')
        ->get();
        $order_status = ['pending', 'processing', 'out_for_delivery',
        'delivered' ,'canceled', 'confirmed', 'scheduled', 'returned' ,
        'faild_to_deliver', 'refund'];
        $preparing_time = $order->branch->food_preparion_time ?? '00:30';
        // if (empty($preparing_time)) {
        $time_parts = explode(':', $preparing_time);

        // _________________________________________
        
        $delivery_time = $this->settings
        ->where('name', 'delivery_time')
        ->orderByDesc('id')
        ->first();
        if (empty($delivery_time)) {
            $delivery_time = $this->settings
            ->create([
                'name' => 'delivery_time',
                'setting' => '00:30:00',
            ]);
        }
        $time_to_add = $delivery_time->setting;
        list($order_hours, $order_minutes, $order_seconds) = explode(':', $time_to_add);
        // Get hours, minutes, and seconds
        $hours = $time_parts[0];
        $minutes = $time_parts[1]; 
        $order_seconds = 0;
        $hours = (int)$hours;
        $minutes = (int)$minutes;
        
        if($order->order_type == 'delivery'){
            // Ensure that $hours, $minutes, and $seconds are integers
            $hours = (int)$hours + (int)$order_hours;
            $minutes = (int)$minutes + (int)$order_minutes;
            $order_seconds = '00';
        }
        $hours += intval($minutes / 60);
        $minutes = $minutes % 60;
        $preparing_arr = [
            'days' => 0,
            'hours' => $hours,
            'minutes' => $minutes,
            'seconds' => 0,
        ];
        //     $preparing_time = $this->settings
        //     ->create([
        //         'name' => 'preparing_time',
        //         'setting' => json_encode($preparing_arr),
        //     ]);
        // }
        // $preparing_time = json_decode($preparing_time->setting);
        $branches = $this->branches
        ->select('name', 'id')
        ->where('status', 1)
        ->get();
        try {
            if($order?->user?->orders){ 
                $order->user->makeHidden("orders");
				$order->user;
            } 
			if($order?->branch){
                unset($order->branch);
				$order->branch;
            }
        } catch (\Throwable $th) {
            //throw $th;
        }

        return response()->json([
            'order' => $order,
            'deliveries' => $deliveries,
            'order_status' => $order_status,
            'preparing_time' => $preparing_arr,
            'branches' => $branches,
        ]);
    }

    public function transfer_branch(Request $request, $id){
        // admin/order/transfer_branch
        // keys => branch_id 
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|exists:branches,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $orders = $this->orders
        ->where('id', $id)
        ->where('branch_id', $request->user()->branch_id)
        ->update([
            'branch_id' => $request->branch_id,
            'operation_status' => 'pending',
            'admin_id' => null,
        ]);  

        return response()->json([
            'success' => 'You update branch success'
        ]);
    }

    public function delivery(Request $request){
        // https://bcknd.food2go.online/admin/order/delivery
        // Keys
        // delivery_id, order_id, order_number
        $validator = Validator::make($request->all(), [
            'delivery_id' => 'required|exists:deliveries,id',
            'order_id' => 'required|exists:orders,id',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $order = $this->orders
        ->where('id', $request->order_id)
        ->first();
        if ($order->order_status != 'processing' && $order->order_status != 'out_for_delivery'
         && $order->order_status != 'confirmed') {
            return response()->json([
                'faild' => 'Status must be processing'
            ], 400);
        } 
        $order->update([
            'delivery_id' => $request->delivery_id,
            'order_number' => $request->order_number ?? $order->order_number,
            'order_status' => 'out_for_delivery',
        ]); 

        return response()->json([
            'success' => 'You select delivery success'
        ]);
    }

    public function status($id, Request $request){
        // https://bcknd.food2go.online/admin/order/status/{id}
        // Keys
        // order_status, order_number
        // if canceled => key admin_cancel_reason
        $validator = Validator::make($request->all(), [
            'order_status' => 'required|in:delivery,confirmed,processing,out_for_delivery,delivered,returned,faild_to_deliver,canceled,scheduled,refund',
            'admin_cancel_reason' => 'required_if:order_status,canceled'
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }

        $order = $this->orders
        ->where('id', $id)
        ->where("branch_id", $request->user()->branch_id)
        ->where(function($query) use($request){
            $query->where('cashier_man_id', $request->user()->id)
            ->orWhereNull('cashier_man_id');
        })
        ->first();
        if(empty($order)){
            return response()->json([
                'errors' => "order not found"
            ], 400);
        }
        
        $old_status = $order->order_status;
        if (empty($order)) {
            return response()->json([
                'errors' => 'order is not found'
            ], 400);
        }
        if ($request->order_status == 'delivered' || $request->order_status == 'returned'
        || $request->order_status == 'faild_to_deliver'|| $request->order_status == 'refund'
        || $request->order_status == 'canceled') {
            $order->update([
                'operation_status' => 'closed',
            ]);
        }
       if ($order->operation_status == 'pending') {
            $order->update([
                'cashier_man_id' => $request->user()->id,
                'cashier_id' => $request->user()->cashier_id,
                'operation_status' => 'opened',
            ]);
        }
        else{
            $arr =  ['pending','processing','confirmed','out_for_delivery','delivered','returned'
            ,'faild_to_deliver','canceled','scheduled','refund'];
            $new_index = array_search($request->order_status, $arr);
            $old_index = array_search($order->order_status, $arr);
        }

        if($old_status == "pending"){
            $order_details = $order->order_details;
            $products = [];
            foreach ($order_details as $item) { 
                $product_item = $item->product[0]; 
                $products[] = [
                    "id" => $product_item->product->id,
                    "count" => $product_item->count,
                ];
            }
            $errors = $this->pull_recipe($products, $order->branch_id); 
            if(!$errors['success']){
                return response()->json([
                    "errors" => $errors['msg']
                ], 400);
            }
        }

        if ($request->order_status == 'processing') { 
            $order->update([
                'order_status' => $request->order_status,
                'order_number' => $request->order_number ?? null,
                'cashier_man_id' => $request->user()->id,
                'cashier_id' => $request->user()->cashier_id,
            ]);
        }
        elseif($request->order_status == 'canceled'){
            // Key
            // admin_cancel_reason
            $validator = Validator::make($request->all(), [
                'admin_cancel_reason' => 'required',
            ]);
            if ($validator->fails()) { // if Validate Make Error Return Message Error
                return response()->json([
                    'errors' => $validator->errors(),
                ],400);
            }
            $data = [
                'name' => $order?->user?->name,
                'reason' => $request->admin_cancel_reason,
            ];
            Mail::to($order->user->email)->send(new CancelOrderMail($data));
            $order->update([
                'order_status' => $request->order_status,
                'admin_cancel_reason' => $request->admin_cancel_reason,
                'cashier_man_id' => $request->user()->id,
                'cashier_id' => $request->user()->cashier_id,
            ]);
        }
        else {
            $order->update([
                'order_status' => $request->order_status, 
                'cashier_man_id' => $request->user()->id,
                'cashier_id' => $request->user()->cashier_id,
            ]);
        } 
        if($request->order_status == 'canceled'){
            $order->admin_cancel_reason = $request->admin_cancel_reason;
            $order->save();
        }

        return response()->json([
            'order_status' => $request->order_status
        ]);
    }

    public function update_order(Request $request, $id){ 
        $validator = Validator::make($request->all(), [
            'amount' => ['required', 'numeric'],
            'total_tax' => ['required', 'numeric'],
            'total_discount' => ['required', 'numeric'],
            'products' => ['required', 'array'],
            'products.*.product_id' => ['exists:products,id', 'required'],
            'products.*.exclude_id.*' => ['exists:exclude_products,id'],
            'products.*.extra_id.*' => ['exists:extra_products,id'],
            'products.*.addons.*.addon_id' => ['exists:addons,id'],
            'products.*.addons.*.count' => ['numeric'],
            'products.*.variation.*.variation_id' => ['exists:variation_products,id'],
            'products.*.variation.*.option_id.*' => ['exists:option_products,id'],
            'products.*.count' => ['numeric', 'required'],
            'products.*.note' => ['sometimes'],
            'financials' => ['required:order_pending,false', 'array'],
            'financials.*.id' => ['required', 'exists:finantiol_acountings,id'],
            'financials.*.amount' => ['required', 'numeric'], 
            'financials.*.description' => ['sometimes'], 
            'financials.*.transition_id' => ['sometimes'], 
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        } 
        $errors = $this->finantion_validation($request);
        if(isset($errors['errors'])){
            return response()->json([
                'errors' => $errors,
            ], 400);
        }
        $order = $this->orders
        ->where('id', $id)
        ->where(function($query){
            $query->where('pos', 1)
            ->orWhere('pos', 0)
            ->where('payment_method_id', 2);
        })
        ->first();
        if(empty($order)){
            return response()->json([
                'errors' => 'You can not update this order'
            ], 400);
        }

        $locale = $request->locale ?? $request->query('locale', app()->getLocale());
        $order_details = $this->order_details($request, $order, $locale);
        $order->order_details = json_encode($order_details['order_details']);
        $order->amount = $request->amount;
        $order->total_tax = $request->total_tax;
        $order->total_discount = $request->total_discount;
        $order->notes = $request->notes; 
        $order->save();
        $order_financial = $this->order_financial
        ->where('order_id', $id)
        ->delete();
        foreach ($request->financials as $element) {
            $this->order_financial
            ->create([
                'order_id' => $order->id,
                'financial_id' => $element['id'],
                'cashier_id' => $request->user()->cashier_id,
                'cashier_man_id' => $request->user()->cashier_man_id,
                'amount' => $element['amount'],
                'description' => isset($element['description']) ? $element['description'] : null,
                'transition_id' => isset($element['transition_id']) ? $element['transition_id'] : null,
            ]); 
            $financial = FinantiolAcounting::
            where("id", $element['id'])
            ->first();
            if($financial){
                $financial->balance += $element['amount'];
                $financial->save();
            }
        }

        return response()->json([
            "success" => "You update order success"
        ]);
    }

    public function finantion_validation($request){
        $financial_ids = array_column($request->financials, 'id');
        $financial_account = $this->financial_account
        ->whereIn("id", $financial_ids)
        ->get();
        foreach ($financial_account as $item) { 
            $result = array_filter($request->financials, function($element) use($item) {
                return $element['id'] == $item->id;
            }); 
            $result = reset($result);
            if($item->description_status){
                if (!isset($result['description'])) {
                    return [
                        "errors" => 'Description is required at financial ' . $item->name
                    ];
                }
                if (!isset($result['transition_id'])) {
                    return [
                        "errors" => 'transition_id is required at financial ' . $item->name
                    ];
                }
            }
        }

        return ["success" => true];
    }
}
