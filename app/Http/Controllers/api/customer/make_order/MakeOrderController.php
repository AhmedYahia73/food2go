<?php

namespace App\Http\Controllers\api\customer\make_order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\customer\order\OrderRequest;
use App\trait\image;
use App\Events\OrderNotification;
use App\trait\PlaceOrder;
use App\trait\PaymentPaymob;

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

class MakeOrderController extends Controller
{
    public function __construct(private Order $order, private OrderDetail $order_details,
    private ProductSale $product_sales, private Product $products, private ExcludeProduct $excludes,
    private ExtraProduct $extras, private Addon $addons, private VariationProduct $variation,
    private OptionProduct $options, private PaymentMethod $paymentMethod, private User $user,
    private PaymentMethodAuto $payment_method_auto,private Setting $settings){}
    use image;
    use PlaceOrder;
    use PaymentPaymob;

    public function order(OrderRequest $request){
        // https://bcknd.food2go.online/customer/make_order
        // Keys
        // date, branch_id, amount, coupon_discount, total_tax, total_discount, address_id
        // order_type[take_away,dine_in,delivery], notes
        // deal[{deal_id, count}], payment_method_id, receipt
        // products[{product_id, addons[{addon_id, count}], exclude_id[], extra_id[], 
        // variation[{variation_id, option_id[]}], count}]
        if ($request->payment_method_id == 1) {
            $payment_method_auto = $this->payment_method_auto
            ->where('payment_method_id', 1)
            ->first();
            $tokens = $this->getToken($payment_method_auto);
            $user = $request->user();
            $amount_cents = $request->amount * 100;
            $order = $this->createOrder($request, $tokens, $user);
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
            return response()->json([
                'success' => $order['payment']->id,
            ]);
        }
        
    }

    public function callback(Request $request){
        $payment_method_auto = $this->payment_method_auto
        ->where('payment_method_id', 1)
        ->first();
        //this call back function its return the data from paymob and we show the full response and we checked if hmac is correct means successfull payment
        $data = $request->all();
        $appUrl = env('Mobile_URL');
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
                    'status' => 1
                ]);
                $user = $this->user
                ->where('id', $order->user_id)
                ->first();
                $user->points += $order->points;
                $user->save();
                return response()->json([
                    'link' => route('customer.callback_success')
                ]);
            //    return view('Paymob.Paymob');
            //    return redirect($appUrl . '://callback_success');
            //    return redirect()->away($redirectUrl . '?' . http_build_query([
            //    'success' => true,
            //    'payment_id' => $payment_id,
            //    'total_amount' => $totalAmount,
            //    "alert('payment Success')"
            //    ]));
               
            } 
            else {        
                $order = $this->order
                ->where('transaction_id', $data['order'])
                ->first();
                $order->update([
                    'payment_status' => 'faild'
                ]); 
                return response()->json([
                    'link' => route('customer.callback_faild')
                ]);
            //    return redirect($appUrl . '://callback_faild');
            }
        }
        else {
            return response()->json([
                'link' => route('customer.callback_faild')
            ]);
        }
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
