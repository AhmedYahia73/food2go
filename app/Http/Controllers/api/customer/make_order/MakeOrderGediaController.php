<?php

namespace App\Http\Controllers\api\customer\make_order;

use Almesery\LaravelGeidea\Facades\Geidea;
use App\Http\Controllers\Controller;
use App\Models\Geidia;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MakeOrderGediaController extends Controller
{ 

    // ✅ استقبال رد Geidea بعد الدفع
    public function callback(Request $request)
    {
        $orderId = $request->query('orderId');

        if (!$orderId) {
            return view('Paymob.FaildPayment');
        }

        $orderResult = Geidea::getOrder($orderId);

        if (!$orderResult['success']) {
            return view('Paymob.FaildPayment');
        }

        // ✅ تحقق من المبلغ
        $order = Order::where('transaction_id', $orderId)->firstOrFail();

        $transactions = $orderResult['order']['transactions'] ?? [];
        $successTxn   = collect($transactions)->firstWhere('status', 'Success');
        $paidAmount   = $successTxn['amount'] ?? 0;

        // ✅ تأكد المبلغ مش اتعدل
        if ((float)$paidAmount !== (float)$order->amount) {
            Log::critical('Amount mismatch', [
                'expected' => $order->amount,
                'paid'     => $paidAmount,
                'order_id' => $order->id,
            ]);
            return view('Paymob.FaildPayment');
        }

        if ($orderResult['order']['status'] === 'Success') {

            // ✅ Idempotency - متشغلش مرتين
            if ($order->status === 1) {
                return view('Paymob.checkout', [
                    'totalAmount' => $order->amount,
                    'message'     => 'تم الدفع مسبقاً',
                    'redirectUrl' => env('WEB_LINK') . '/orders/order_traking/' . $order->id,
                    'timer'       => 3,
                ]);
            }

            $order->update([
                'status'       => 1,
                'order_status' => 'processing',
            ]);

            $user = User::find($order->user_id);
            if ($user) {
                $user->increment('points', $order->points);
            }

            return view('Paymob.checkout', [
                'totalAmount' => $order->amount,
                'message'     => 'Your payment is being processed. Please wait...',
                'redirectUrl' => env('WEB_LINK') . '/orders/order_traking/' . $order->id,
                'timer'       => 3,
            ]);
        }

        return view('Paymob.FaildPayment');
    }
    
    public function paymentPage(Request $request)
    {
        $settings = Geidia::first();

        // ✅ حمّل الـ config الأول
        config([
            'geidea.merchant_public_key' => $settings->geidea_public_key,
            'geidea.api_password'        => $settings->api_password,
            'geidea.environment'         => $settings->environment,
            'geidea.currency'            => 'EGP',
            'geidea.language'            => 'ar',
        ]);

        return view('Geida.Geida', [
            'sessionId'   => $request->session_id,
            'merchantKey' => \Almesery\LaravelGeidea\Facades\Geidea::getMerchantPublicKey(),
            'hppScript'   => \Almesery\LaravelGeidea\Facades\Geidea::getHppScriptUrl(),
        ]);
    }

    public function return_page(Request $request)
    {
        $geideaOrderId = $request->query('orderId');
        $order = Order::where('transaction_id', $geideaOrderId)->firstOrFail();
        return redirect(env('WEB_LINK') . '/orders/order_traking/' . $order->id);
    }
}
