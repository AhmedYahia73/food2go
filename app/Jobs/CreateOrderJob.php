<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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

class CreateOrderJob implements ShouldQueue
{
    use Queueable; 
    protected $request; 
        /**
     * Create a new job instance.
     */
    public function __construct($request)
    {
        $this->request = $request; 
    }
    use image;
    use PlaceOrder;
    use PaymentPaymob;
    /**
     * Execute the job.
     */
    public function handle(): void
    {    
         $order =new Order;   $order_details =new OrderDetail;
          $product_sales =new ProductSale;  $products =new Product;  $excludes =new ExcludeProduct;
          $extras =new ExtraProduct;   $addons =new Addon;   $variation =new VariationProduct;
          $options =new OptionProduct;   $paymentMethod =new PaymentMethod;   $user =new User;
          $payment_method_auto =new PaymentMethodAuto;  $settings =new Setting;
        if ($request->payment_method_id == 1) {
            $payment_method_auto = $this->payment_method_auto
            ->where('payment_method_id', 1)
            ->first();
            $tokens = $this->getToken($payment_method_auto);
            $user = User::find($this->userId);;
            $amount_cents = $request->amount * 100;
            $order = $this->createOrder($request, $tokens, $user);
            if (is_array($order) && isset($order['errors']) && !empty($order['errors'])) {     
                \Log::error('Order creation failed', $order);
                return;
            }
            $order_id = $this->order
            ->where('transaction_id', $order->id)
            ->first();
            // $order = $this->make_order($request);
            // $order = $order['payment']; 
            $paymentToken = $this->getPaymentToken($user, $amount_cents, $order, $tokens, $payment_method_auto);
            $paymentLink = "https://accept.paymob.com/api/acceptance/iframes/" . $payment_method_auto->iframe_id . '?payment_token=' . $paymentToken;
           
            \Log::info('Payment link generated: ' . $paymentLink);
        } 
        else {
            $order = $this->make_order($request);
            if (isset($order['errors']) && !empty($order['errors'])) {
                \Log::error('Order failed', $order);
                return;
            }
            \Log::info('Order created with ID: ' . $order['payment']->id);
        }
    }
}
