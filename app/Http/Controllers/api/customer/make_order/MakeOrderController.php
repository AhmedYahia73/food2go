<?php

namespace App\Http\Controllers\api\customer\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\order\OrderRequest;
use App\trait\image;
use App\Events\OrderNotification;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;
use Carbon\Carbon;
use App\Events\OrderEvent;
use App\trait\Notifications; 

use App\Models\CompanyInfo;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductSale;
use App\Models\Product;
use App\Models\ExcludeProduct;
use App\Models\ExtraProduct;
use App\Models\VariationProduct;
use App\Models\OptionProduct;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Addon;
use App\Models\PaymentMethodAuto;
use App\Models\Setting;
use App\Models\TimeSittings;
use App\Models\Branch;
use App\Models\Address;
use App\Models\DeviceToken;
use App\Models\NewNotification;
use Almesery\LaravelGeidea\Facades\Geidea;

class MakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetail $order_details,
    private ProductSale $product_sales, private Product $products, private ExcludeProduct $excludes,
    private ExtraProduct $extras, private Addon $addons, private VariationProduct $variation,
    private OptionProduct $options, private PaymentMethod $paymentMethod, private User $user,
    private PaymentMethodAuto $payment_method_auto,private Setting $settings,
    private Address $address, private TimeSittings $TimeSittings,
    private CompanyInfo $company_info, private Branch $branches,
    private DeviceToken $device_tokens){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;
    use Notifications;

    public function order(OrderRequest $request){
        // https://bcknd.food2go.online/customer/make_order
        // Keys
        // date, branch_id, amount, coupon_discount, total_tax, total_discount, 
        // address_id, source
        // order_type[take_away,dine_in,delivery]
        // deal[{deal_id, count}], c, receipt
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count, note}], sechedule_slot_id,
        // coupon_id
        if ($request->user()->status == 0) {
            return response()->json([
                'errors' => "You are blocked you can't make order"
            ], 400);
        }
        $company_info = $this->company_info
        ->first();
        if (!$company_info->order_online) {
            return response()->json([
                'errors' => 'online order is closed'
            ], 400);
        }
        if (!empty($request->address_id) && empty($request->branch_id)) {
            $address = $this->address
            ->where('id', $request->address_id)
            ->first();
            $branch_id = $address?->zone?->branch_id ?? null;
            $delivery_fees = $address?->zone?->price ?? 0;
            $request->merge([
                'branch_id' => $branch_id,
                'delivery_fees' => $delivery_fees,
            ]);
        }
        $branche = $this->branches
        ->where('id', $request->branch_id)
        ->orderBy('order')
        ->where('status', 1)
        ->first();

        if(empty($branche)){
            return response()->json([
                'errors' => 'this branch is locked'
            ], 422);
        }
        // Time Slot
            // $resturant_time = $time_sitting;
            // $time_slot = json_decode($time_slot->setting);
            // $days = $time_slot->custom;
            // $open_from = $resturant_time->from;
            
            // _________________________________________________________________
            $time_sitting = $this->TimeSittings
            ->where('branch_id', $request->branch_id ?? null)
            ->get();
            $today = Carbon::now()->format('l');
            $close_message = '';
            $open_flag = false;

            if($time_sitting->count() == 0){
                $open_flag = true;
            }
            else{
                $now = Carbon::now();
                foreach ($time_sitting as $item) { 
                    $resturant_time = $item;
                    $open_from = date('Y-m-d') . ' ' . $resturant_time->from;

                    $open_from = Carbon::createFromFormat('Y-m-d H:i:s', $now->format('Y-m-d') . ' ' . $resturant_time->from);
                    $open_to = $open_from->copy()->addHours(intval($resturant_time->hours));
                    if($now >= $open_from && $now <= $open_to){
                        $open_flag = true;
                        break;
                    }
                    else{
                        $open_flag = false;
                    }
                }
            }
            // _________________________________________________________________
            //     if (!empty($open_from)) {
            //         $open_from = Carbon::createFromFormat('H:i:s', $open_from); 
            //         $open_to = $open_from->copy()->addHours(intval($resturant_time->hours));
            //         $today = Carbon::now()->format('l');
            //         $now = Carbon::now(); // Don't override this later
            //         $open_flag = false;
            //         $open_from = $open_from;
            //         $open_to = $open_to; 
            //         if ($now->between($open_from, $open_to) && !in_array($today, $days)) {
            //             $open_flag = true;
            //         }
            //     }
            //     else{
            //         $open_flag = true;
            //     }
            // }
                if (!$open_flag) {
                    return response()->json([
                        'errors' => 'Resurant is closed'
                    ], 403);
                }
        // Check if has order at proccessing
        $order = $this->order
        ->whereIn('order_status', ['pending', 'processing', 'confirmed', 'out_for_delivery', 'scheduled'])
        ->where(function($query){
            $query->where("status", 1)
            ->orWhereNull("status");
        })
        ->where('user_id', $request->user()->id)
        ->first();
        if (!empty($order) && !$request->confirm_order) {
            return response()->json([
                'errors' => 'You has order at proccessing',
                'data' => $order->order_details
            ], 510);
        }
        // Make Order
        if ($request->payment_method_id == 1) {
            $payment_method_auto = $this->payment_method_auto
            ->where('payment_method_id', 1)
            ->first();
            $tokens = $this->getToken($payment_method_auto);
            $user = $request->user();
            $amount_cents = $request->amount * 100;
            $order = $this->createOrder($request, $tokens, $user);
            if (is_array($order) && isset($order['errors']) && !empty($order['errors'])) {
                return response()->json($order, 400);
            }
            $order_id = $this->order
            ->where('transaction_id', $order->id)
            ->first(); 
            // $order = $this->make_order($request);
            // $order = $order['payment']; 
            $paymentToken = $this->getPaymentToken($user, $amount_cents, $order, $tokens, $payment_method_auto);
            $paymentLink = "https://accept.paymob.com/api/acceptance/iframes/" . $payment_method_auto->iframe_id . '?payment_token=' . $paymentToken;
            
            return response()->json([
                'success' => $order_id->id,
                'paymentLink' => $paymentLink,
            ]);
        } 
        else {
            $order = $this->make_order($request);
            if (isset($order['errors']) && !empty($order['errors'])) {
                return response()->json($order, 400);
            } // new_order
            OrderEvent::dispatch($order['payment']);

            $body = 'New Order #' . $order['payment']->order_number;
            $device_token = $this->device_tokens
            ->whereNotNull('admin_id')
            ->get()
            ?->pluck("fcm_token")
            ?->toArray();
            $this->sendNotificationToMany($device_token, $order['payment']->order_number, $body);
            return response()->json([
                'success' => $order['payment']->id,
                'gedia' => $order['gedia'],
                "gedia_status" => $order['gedia_status'],
            ]);
        }
        // CreateOrderJob::dispatch($request);
        
    }

    // public function order_from_cart(\App\Http\Requests\customer\order\OrderFromCartRequest $request){
    //     if ($request->user()->status == 0) {
    //         return response()->json(['errors' => "You are blocked you can't make order"], 400);
    //     }
    //     $company_info = $this->company_info->first();
    //     if (!$company_info->order_online) {
    //         return response()->json(['errors' => 'online order is closed'], 400);
    //     }

    //     $branch_id = $request->branch_id ?? null;
    //     if (!empty($request->address_id) && empty($request->branch_id)) {
    //         $address = $this->address->where('id', $request->address_id)->first();
    //         $branch_id = $address?->zone?->branch_id ?? null;
    //         $delivery_fees = $address?->zone?->price ?? 0;
    //         $request->merge([
    //             'branch_id' => $branch_id,
    //             'delivery_fees' => $delivery_fees,
    //         ]);
    //     }
        
    //     $user = $request->user();
    //     $cart_data = $this->calculate_cart_totals($request, $user);
    //     if (!$cart_data) {
    //         return response()->json(['errors' => 'Cart is empty'], 400);
    //     }
        
    //     $request->merge([
    //         'amount' => $cart_data['amount'],
    //         'total_tax' => $cart_data['total_tax'],
    //         'total_discount' => $cart_data['total_discount']
    //     ]);

    //     $branche = $this->branches->where('id', $branch_id)->orderBy('order')->where('status', 1)->first();

    //     if(empty($branche)){
    //         return response()->json(['errors' => 'this branch is locked'], 422);
    //     }
        
    //     $time_sitting = $this->TimeSittings
    //     ->where('branch_id', $branch_id)
    //     ->get();
    //     $today = Carbon::now()->format('l');
    //     $close_message = '';
    //     $open_flag = false;
    //     $start = Carbon::parse(date('Y-m-d') . ' 00:00:00');
    //     $end = Carbon::parse(date('Y-m-d') . ' 23:59:59');

    //     if($time_sitting->count() == 0){
    //         $open_flag = true;
    //     }
    //     else{  
    //         foreach ($time_sitting as $item) { 
                  
    //                 $start = date("Y-m-d") . ' ' . $item->from;  
    //                 $hours = $item->hours;
    //                 $minutes = $item->minutes; 
    //                 $start = Carbon::parse($start); 
    //                 $end = Carbon::parse($start)->addHours($hours)->addMinutes($minutes);
  
    //                 if($start <= now() && now() <= $end){
    //                     $open_flag = true; 
    //                 }
    //         } 
    //     }
    //     if (!$open_flag) {
    //         return response()->json([
    //             'errors' => 'Resurant is closed'
    //         ], 403);
    //     }
    //     $order = $this->order->whereIn('order_status', ['pending', 'processing', 'confirmed', 'out_for_delivery', 'scheduled'])
    //     ->where(function($query){ $query->where("status", 1)->orWhereNull("status"); })
    //     ->where('user_id', $request->user()->id)->first();
    //     if (!empty($order) && !$request->confirm_order) {
    //         return response()->json(['errors' => 'You has order at proccessing', 'data' => $order->order_details], 510);
    //     }

    //     if ($request->payment_method_id == 1) {
    //         $payment_method_auto = $this->payment_method_auto->where('payment_method_id', 1)->first();
    //         $tokens = $this->getToken($payment_method_auto);
            
    //         $order_data = $this->make_order_from_cart($request, 1);
    //         if (isset($order_data['errors']) && !empty($order_data['errors'])) return response()->json($order_data, 400);

    //         $totalAmountCents = (int) round($order_data['payment']->amount * 100);
    //         $data = [
    //             "auth_token" => $tokens,
    //             "delivery_needed" =>"false",
    //             "amount_cents"=> $totalAmountCents,
    //             "currency"=> "EGP",
    //             "items"=> $order_data['items'],
    //         ];
    //         $response = \Illuminate\Support\Facades\Http::post('https://accept.paymob.com/api/ecommerce/orders', $data);
            
    //         if (!$response->successful() || !isset($response['id'])) {
    //             return response()->json(['errors' => 'Paymob payment gateway error: ' . ($response->json('message') ?? $response->json('detail') ?? 'Failed to place order.')], 422);
    //         }
            
    //         $order_id_paymob = $response['id'];
    //         $payment_model = $order_data['payment'];
    //         $payment_model->update(['transaction_id' => $order_id_paymob]);
    //         $order_resp = $response->object();
            
    //         $paymentToken = $this->getPaymentToken($user, $totalAmountCents, $order_resp, $tokens, $payment_method_auto);
    //         $paymentLink = "https://accept.paymob.com/api/acceptance/iframes/" . $payment_method_auto->iframe_id . '?payment_token=' . $paymentToken;
            
    //         \App\Models\ProductCart::where('user_id', $user->id)->delete();
            
    //         return response()->json([
    //             'success' => $payment_model->id,
    //             'paymentLink' => $paymentLink,
    //         ]);
    //     } else {
    //         $order_data = $this->make_order_from_cart($request);
    //         if (isset($order_data['errors']) && !empty($order_data['errors'])) {
    //             return response()->json($order_data, 400);
    //         }
            
    //         \App\Models\ProductCart::where('user_id', $user->id)->delete();
            
    //         OrderEvent::dispatch($order_data['payment']);

    //         $body = 'New Order #' . $order_data['payment']->order_number;
    //         $device_token = $this->device_tokens->whereNotNull('admin_id')->get()?->pluck("fcm_token")?->toArray();
    //         $this->sendNotificationToMany($device_token, $order_data['payment']->order_number, $body);
    //         return response()->json([
    //             'success' => $order_data['payment']->id,
    //             'gedia' => $order_data['gedia'],
    //             "gedia_status" => $order_data['gedia_status'],
    //         ]);
    //     }
    // }
    public function order_from_cart(\App\Http\Requests\customer\order\OrderFromCartRequest $request)
    {
        if ($request->user()->status == 0) {
            return response()->json(['errors' => "You are blocked you can't make order"], 400);
        }

        $company_info = $this->company_info->first();
        if (!$company_info || !$company_info->order_online) {
            return response()->json(['errors' => 'online order is closed'], 400);
        }

        $branch_id = $request->branch_id ?? null;
        if (!empty($request->address_id) && empty($request->branch_id)) {
            $address = $this->address->where('id', $request->address_id)->first();
            $branch_id = $address?->zone?->branch_id ?? null;
            $delivery_fees = $address?->zone?->price ?? 0;
            $request->merge([
                'branch_id' => $branch_id,
                'delivery_fees' => $delivery_fees,
            ]);
        }

        if (!$branch_id) {
            return response()->json(['errors' => 'Branch is required'], 422);
        }

        $user = $request->user();
        $cart_data = $this->calculate_cart_totals($request, $user);
        if (!$cart_data) {
            return response()->json(['errors' => 'Cart is empty'], 400);
        }

        $request->merge([
            'amount' => $cart_data['amount'],
            'total_tax' => $cart_data['total_tax'],
            'total_discount' => $cart_data['total_discount']
        ]);

        $branche = $this->branches->where('id', $branch_id)->orderBy('order')->where('status', 1)->first();

        if (empty($branche)) {
            return response()->json(['errors' => 'this branch is locked'], 422);
        }

        // ==================== [ تحسين فحص مواعيد العمل ] ====================
        $today = Carbon::now()->format('l'); // اسم اليوم بالكامل e.g., "Sunday"

        // الفلترة بالفرع واليوم الحالي مباشرة
        $time_sitting = $this->TimeSittings
            ->where('branch_id', $branch_id)
            ->where('day', $today) // تأكد من مطابقة اسم العمود في قاعدة البيانات عندك (day أو day_name)
            ->get();

        $open_flag = false;

        if ($time_sitting->count() == 0) {
            $open_flag = true;
        } else {
            $now = Carbon::now();

            foreach ($time_sitting as $item) {
                $hours = $item->hours ?? 0;
                $minutes = $item->minutes ?? 0;

                // إنشاء كائن بداية الفترة
                $start = Carbon::parse(date('Y-m-d') . ' ' . $item->from);
                
                // إنشاء كائن نهاية الفترة باستنسال (clone) لعدم التعديل على $start
                $end = (clone $start)->addHours($hours)->addMinutes($minutes);

                if ($now->between($start, $end)) {
                    $open_flag = true;
                    break; // إيقاف التكرار فور التحقق لتوفير الأداء
                }
            }
        }

        if (!$open_flag) {
            return response()->json([
                'errors' => 'Restaurant is closed'
            ], 403);
        }
        // ===================================================================

        $order = $this->order->whereIn('order_status', ['pending', 'processing', 'confirmed', 'out_for_delivery', 'scheduled'])
            ->where(function ($query) {
                $query->where("status", 1)->orWhereNull("status");
            })
            ->where('user_id', $request->user()->id)->first();

        if (!empty($order) && !$request->confirm_order) {
            return response()->json(['errors' => 'You has order at proccessing', 'data' => $order->order_details], 510);
        }

        if ($request->payment_method_id == 1) {
            $payment_method_auto = $this->payment_method_auto->where('payment_method_id', 1)->first();
            $tokens = $this->getToken($payment_method_auto);

            $order_data = $this->make_order_from_cart($request, 1);
            if (isset($order_data['errors']) && !empty($order_data['errors'])) return response()->json($order_data, 400);

            $totalAmountCents = (int) round($order_data['payment']->amount * 100);
            $data = [
                "auth_token" => $tokens,
                "delivery_needed" => "false",
                "amount_cents" => $totalAmountCents,
                "currency" => "EGP",
                "items" => $order_data['items'],
            ];
            $response = \Illuminate\Support\Facades\Http::post('https://accept.paymob.com/api/ecommerce/orders', $data);

            if (!$response->successful() || !isset($response['id'])) {
                return response()->json(['errors' => 'Paymob payment gateway error: ' . ($response->json('message') ?? $response->json('detail') ?? 'Failed to place order.')], 422);
            }

            $order_id_paymob = $response['id'];
            $payment_model = $order_data['payment'];
            $payment_model->update(['transaction_id' => $order_id_paymob]);
            $order_resp = $response->object();

            $paymentToken = $this->getPaymentToken($user, $totalAmountCents, $order_resp, $tokens, $payment_method_auto);
            $paymentLink = "https://accept.paymob.com/api/acceptance/iframes/" . $payment_method_auto->iframe_id . '?payment_token=' . $paymentToken;

            \App\Models\ProductCart::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => $payment_model->id,
                'paymentLink' => $paymentLink,
            ]);
        } else {
            $order_data = $this->make_order_from_cart($request);
            if (isset($order_data['errors']) && !empty($order_data['errors'])) {
                return response()->json($order_data, 400);
            }

            \App\Models\ProductCart::where('user_id', $user->id)->delete();

            OrderEvent::dispatch($order_data['payment']);

            $body = 'New Order #' . $order_data['payment']->order_number;
            $device_token = $this->device_tokens->whereNotNull('admin_id')->pluck("fcm_token")->filter()->toArray();
            
            if (!empty($device_token)) {
                $this->sendNotificationToMany($device_token, $order_data['payment']->order_number, $body);
            }

            return response()->json([
                'success' => $order_data['payment']->id,
                'gedia' => $order_data['gedia'],
                "gedia_status" => $order_data['gedia_status'],
            ]);
        }
    }
    public function callback(Request $request){
        // https://bcknd.food2go.online/customer/callback
        $payment_method_auto = $this->payment_method_auto
        ->where('payment_method_id', 1)
        ->first();
        
        //this call back function its return the data from paymob and we show the full response and we checked if hmac is correct means successfull payment
        $data = $request->all();
        ksort($data);
        $hmac = $data['hmac'];
        $array = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order',
            'owner',
            'pending',
            'source_data_pan',
            'source_data_sub_type',
            'source_data_type',
            'success',
        ];
        $connectedString = '';
        foreach ($data as $key => $element) {
            if (in_array($key, $array)) {
                $connectedString .= $element;
            }
        }
        $secret = $payment_method_auto->Hmac;
        $hased = hash_hmac('sha512', $connectedString, $secret);
        if ($hased == $hmac) {
            //this below data used to get the last order created by the customer and check if its exists to 
            // $todayDate = Carbon::now();
            // $datas = Order::where('user_id',Auth::user()->id)->whereDate('created_at',$todayDate)->orderBy('created_at','desc')->first();
            $status = $data['success']; 
            // $pending = $data['pending'];
            if ($status == "true") { 
                //here we checked that the success payment is true and we updated the data base and empty the cart and redirct the customer to thankyou page
                $order = $this->order
                ->where('transaction_id', $data['order'])
                ->first();
                $order->update([
                    'status' => 1,
                    'order_status' => 'processing'
                ]);
                $user = $this->user
                ->where('id', $order->user_id)
                ->first();
                if(!empty($user)){
                    $user->points += $order->points;
                    $user->save();
                }
                $totalAmount = $data['amount_cents'];
                $message = 'Your payment is being processed. Please wait...';
                $redirectUrl = env('WEB_LINK') . '/order_traking/' . $order->id;
                $timer = 3; // 3  seconds
                OrderEvent::dispatch($order);
                $body = 'New Order #' . $order->order_number;
                $device_token = $this->device_tokens
                ->whereNotNull('admin_id')
                ->get()
                ?->pluck("fcm_token")
                ?->toArray();
                $this->sendNotificationToMany($device_token, $order->order_number, $body);
                if($order->source == 'web'){
                    return  view('Paymob.checkout', compact('totalAmount','message','redirectUrl','timer'));
                }
                else{
                    return response()->json([
                        'success' => 'You payment success'
                    ]);
                }
            }
            else {        
                $order = $this->order
                ->where('transaction_id', $data['order'])
                ->first();
                $order->update([
                    'payment_status' => 'faild'
                ]); 
               return  view('Paymob.FaildPayment');
            //    return redirect($appUrl . '://callback_faild');
            }
        }
        else {
               return  view('Paymob.FaildPayment');
        }
             
        return response()->json([
            'success' => 'You payment success'
        ]);
    }

    public function callback_success(){
        return view('Paymob.Paymob');
    }

    public function callback_faild(){
        return view('Paymob.FaildPayment');
    }

    public function callback_status($id){
        // https://bcknd.food2go.online/customer/make_order/callback_status/{id}
        $order = $this->order
        ->where('id', $id)
        ->first();
        if ($order->status == 1) {
            return response()->json([
                'status' => 'success'
            ]);
        } 
        else {
            return response()->json([
                'status' => 'faild'
            ], 400);
        }
        
    }
}
