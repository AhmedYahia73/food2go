<?php

namespace App\Http\Controllers\api\customer\order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Validator;
use App\trait\Notifications; 

use App\Models\NewNotification; 

use App\Models\Order;
use App\Models\Setting;
use App\Models\Product;
use App\Models\DeviceToken;

class OrderController extends Controller
{
    public function __construct(private Order $orders, private Setting $settings,
    private NewNotification $notification, private DeviceToken $device_token){}
    use Notifications;

    public function upcomming(Request $request){
        // https://bcknd.food2go.online/customer/orders
        $orders = $this->orders
        ->where('user_id', $request->user()->id)
        ->whereIn('order_status', ['pending', 'confirmed', 'processing', 'out_for_delivery', 'scheduled'])
        ->where(function($query) {
            $query->where('status', 1)
            ->orWhereNull('status');
        }) 
        ->with('delivery', 'payment_method')
        ->get()
        ->map(function($item){ 
            $total_variation = collect($item->order_details);  
            $addons = collect($total_variation->pluck('addons'))->flatten(1); 
            $addons = collect($addons)->map(function ($item) {
                $addon = $item->addon;
                $addon->count = (int)$item->count;
                return $addon;
            });
            $total_variation = collect($total_variation->pluck('variations'));
            if ($total_variation->count() > 0) {
                $total_variation = collect($total_variation[0])->pluck('options')->flatten(1);
            } 
            $total_product = collect($item->order_details);
            $total_product = collect($total_product?->pluck('product')); 
            $total = 0;
            $products = [];
            if ($total_product->count() > 0) {
                $total_product = collect($total_product);
                foreach ($total_product as $item) {
                    $item = collect($item);
                    foreach ($item as $element) {
                        $product = $element->product;
                        $product = collect($product);
                        unset($product->addons);
                        $total = ($product->price + $total_variation
                        ->where('product_id', $product->id)
                        ->sum('price')) * $element->count;
                        $product->total_product = $total;
                        $product->count = $element->count;
                        $product->note = isset($element?->notes) ? $element?->notes :  null;
                        $products[] = $product;
                    }
                }
            }
            $products = collect($products)?->select('id', 'total_product', 'name', 'image_link', 'count', 'note');
            return [
                'id' => $item->id,
                'date' => $item->date,
                'amount' => $item->amount,
                'order_status' => $item->order_status,
                'order_type' => $item->order_type,
                'total_discount' => $item->total_discount,
                'notes' => $item->notes,
                'rejected_reason' => $item->rejected_reason,
                'customer_cancel_reason' => $item->customer_cancel_reason,
                'admin_cancel_reason' => $item->admin_cancel_reason,
                'delivery_price' => $item->delivery_price,
                'products' => $products,
                'payment_method' => $item?->payment_method?->name,
                'delivery_price' => $item?->order_address?->zone?->price ?? null,
                'branch_name' => $item?->branch?->name ?? null,
                'address_name' => $item?->order_address?->address ?? null,
                'addons' => $addons,
                'order_date' => $item->order_date,
                'can_cancel' => $item->order_status == 'pending' ? true : false,

            ];
        });
        $cancel_time = $this->settings
        ->where('name', 'time_cancel')
        ->orderByDesc('id')
        ->first();
        $cancel_time = $cancel_time->setting ?? '00:00:00';

        return response()->json([
            'orders' => $orders,
            'cancel_time' => $cancel_time,
        ]);
    }

    public function order_history(Request $request){
        // https://bcknd.food2go.online/customer/orders/history
        $orders = $this->orders
        ->orderByDesc('id')
        ->where('user_id', $request->user()->id)
        ->whereIn('order_status', ['delivered', 'faild_to_deliver', 'canceled'])
        ->with('payment_method')
        ->where('deleted_at', 0)
        ->get()
        ->map(function($item){
            $total_variation = collect($item->order_details);  
            $addons = collect($total_variation->pluck('addons'))->flatten(1); 
            $addons = collect($addons)->map(function ($item) {
                $addon = $item->addon;
                $addon->count = (int)$item->count;
                return $addon;
            });
            $total_variation = collect($total_variation->pluck('variations'));
            if ($total_variation->count() > 0) {
                $total_variation = collect($total_variation[0])->pluck('options')->flatten(1);
            } 
            $total_product = collect($item->order_details);
            $total_product = collect($total_product?->pluck('product')); 
            $total = 0;
            $products = [];
            if ($total_product->count() > 0) {
                $total_product = collect($total_product[0]); 
                foreach ($total_product as $element) {
                    $product = $element->product;
                    unset($product->addons);
                    $total = ($product->price + $total_variation
                    ->where('product_id', $product->id)
                    ->sum('price')) * $element->count;
                    $product->total_product = $total;
                    $product->count = $element->count;
                    $product->note = isset($element?->notes) ? $element?->notes :  null;
                    $products[] = $product;
                }
            }
            $products = collect($products)?->select('id', 'total_product', 'name', 'image_link', 'count', 'note');
            return [
                'id' => $item->id,
                'date' => $item->date,
                'amount' => $item->amount,
                'order_status' => $item->order_status,
                'order_type' => $item->order_type,
                'total_discount' => $item->total_discount,
                'notes' => $item->notes,
                'rejected_reason' => $item->rejected_reason,
                'customer_cancel_reason' => $item->customer_cancel_reason,
                'admin_cancel_reason' => $item->admin_cancel_reason,
                'delivery_price' => $item->delivery_price,
                'products' => $products,
                'payment_method' => $item?->payment_method?->name,
                'delivery_price' => $item?->order_address?->zone?->price ?? null,
                'branch_name' => $item?->branch?->name ?? null,
                'address_name' => $item?->order_address?->address ?? null,
                'addons' => $addons,
                'order_date' => $item->order_date,
            ];
        });

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function order_track($id){
        // https://bcknd.food2go.online/customer/orders/order_status/{id}
        $order = $this->orders
        ->where('id', $id)
        ->first();
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
        $delivery_phone = $order?->delivery?->phone;

        $branch = $order?->branch?->food_preparion_time ?? '00:00';

        // Assuming $order->created_at is a valid date-time string in 'Y-m-d H:i:s' format
        $time = Carbon::createFromFormat('Y-m-d H:i:s', $order->created_at);
        
        // Get the time to add (e.g., '02:30:45')
        $time_to_add = $delivery_time->setting;  // Assuming this is something like '02:30:45'
        
        // Split the time string into hours, minutes, and seconds
        list($hours, $minutes, $seconds) = explode(':', $time_to_add);
        list($order_hours, $order_minutes) = explode(':', $branch);
        $order_seconds = 0;
        $order_hours = (int)$order_hours;
        $order_minutes = (int)$order_minutes;
        
        if($order->order_type == 'delivery'){
            // Ensure that $hours, $minutes, and $seconds are integers
            $order_hours = (int)$hours + (int)$order_hours;
            $order_minutes = (int)$minutes + (int)$order_minutes;
            $order_seconds = (int)$seconds + (int)$order_seconds;
        }
        
        // Add the time to the original Carbon instance
        $time = $time->addHours($order_hours)->addMinutes($order_minutes)->addSeconds($order_seconds);
        
        // If you want to format the final time as 'H:i:s'
        $formattedTime = $time->format('H:i:s');
        $formattedTime = Carbon::createFromFormat('H:i:s', $formattedTime)->format('h:i:s A');
 		$order_hours += intval($order_minutes / 60);
        $order_minutes = $order_minutes % 60;
		$order_hours = str_pad($order_hours, 2, '0', STR_PAD_LEFT);
		$order_minutes = str_pad($order_minutes, 2, '0', STR_PAD_LEFT);
 		$my_delivery_time = $order_hours . ':' . $order_minutes . ':00';
		$delivery_time->setting = $my_delivery_time;

        return response()->json([
            'status' => $order->order_status,
            'delivery_id' => $order->delivery_id,
            'delivery_time' =>$delivery_time,
            'time_delivered' => $formattedTime,
            'customer_cancel_reason' => $order->customer_cancel_reason,
            'admin_cancel_reason' => $order->admin_cancel_reason,
            'delivery_phone' => $delivery_phone,
            'order_date' => $order->order_date,
        ]);
    }

    // public function notification_sound(){
    //     // https://bcknd.food2go.online/customer/orders/notification_sound
    //     $notification_sound = $this->settings
    //     ->where('name', 'notification_sound')
    //     ->orderByDesc('id')
    //     ->first();
    //     if (empty($notification_sound)) {
    //         $notification_sound = null;
    //     }
    //     else{
    //         $notification_sound = url('storage/' . $notification_sound->setting);
    //     }

    //     return response()->json([
    //         'notification_sound' => $notification_sound
    //     ]);
    // }

    public function cancel(Request $request, $id){
        // https://bcknd.food2go.online/customer/orders/cancel/{id}
        // Key
        // customer_cancel_reason
        // composer require kreait/laravel-firebase
        $validator = Validator::make($request->all(), [
            'customer_cancel_reason' => 'required',
        ]);
        if ($validator->fails()) { // if Validate Make Error Return Message Error
            return response()->json([
                'errors' => $validator->errors(),
            ],400);
        }
        $order = $this->orders
        ->where('id', $id)
        ->first();
        if(empty($order) || $order->order_status != 'pending'){
            return response()->json([
                'errors' => "order is preparing ypu can't cancel it",
            ], 400);
        }
        $order->update([
            'customer_cancel_reason' => $request->customer_cancel_reason,
            'order_status' => 'canceled'
        ]);
        $notification = $this->notification
        ->create([
            'title' => $id,
            'notification' => $request->customer_cancel_reason, 
        ]);
        $device_token = $this->device_token
        ->get()
        ?->pluck('token')
        ?->toArray();
        $this->sendNotificationToMany($device_token, $id, $request->customer_cancel_reason);
        
        return response()->json([
            'success' => 'You cancel order success'
        ]);
    }

    public function cancel_time(){
        // https://bcknd.food2go.online/customer/orders/cancel_time
        $cancel_time = $this->settings
        ->where('name', 'time_cancel')
        ->orderByDesc('id')
        ->first();
        $cancel_time = $cancel_time->setting ?? '00:00:00';

        return response()->json([
            'cancel_time' => $cancel_time
        ]);
    }
}
