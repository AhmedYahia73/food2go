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
            return redirect(env('WEB_LINK'));
        }

        $orderResult = Geidea::getOrder($orderId);

        if (!$orderResult['success']) {
            return redirect(env('WEB_LINK'));
        }

        // Get merchant reference ID from Geidea response
        $merchantReferenceId = $orderResult['order']['merchantReferenceId'] ?? null;
        
        // Extract order ID from merchant reference (e.g., "ORDER4957" -> 4957)
        $localOrderId = null;
        if ($merchantReferenceId && preg_match('/ORDER(\d+)/', $merchantReferenceId, $matches)) {
            $localOrderId = $matches[1];
        }

        // Find order by ID or transaction_id
        $order = Order::where('id', $localOrderId)
                     ->orWhere('transaction_id', $orderId)
                     ->first();

        if (!$order) {
            \Log::error('Order not found', [
                'geidea_order_id' => $orderId,
                'merchant_reference' => $merchantReferenceId,
            ]);
            return redirect(env('WEB_LINK'));
        }

        // Update transaction_id if not set
        if (empty($order->transaction_id)) {
            $order->update(['transaction_id' => $orderId]);
        }

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
            return redirect(env('WEB_LINK'));
        }

        if ($orderResult['order']['status'] === 'Success') {

            // ✅ Idempotency - متشغلش مرتين
            if ($order->status === 1) {
                return redirect(env('WEB_LINK') . '/orders/order_traking/' . $orderId);
            }

            $order->update([
                'status'       => 1,
                'order_status' => 'processing',
            ]);

            $user_item = User::
            where("id", $order->user_id )
            ->first();
            if($user_item){
                $user_item->update([
                    "points" => $user_item->points + $order->points
                ]);
            }
            $user = User::find($order->user_id);
            if ($user) {
                $user->increment('points', $order->points);
            }

            return redirect(env('WEB_LINK') . '/orders/order_traking/' . $orderId);
        }
        return redirect(env('WEB_LINK'));
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
        $responseCode  = $request->query('responseCode');
        $sessionId     = $request->query('sessionId');

        // Cancelled or failed
        if (!$geideaOrderId || $geideaOrderId === 'null' || $responseCode !== '000') {
            // Try to find order by session if possible
            return redirect(env('WEB_LINK'));
        }

        // Success - find order by transaction_id or merchant reference
        $order = Order::where('transaction_id', $geideaOrderId)->first();

        if (!$order) {
            return redirect(env('WEB_LINK'));
        }

        return redirect(env('WEB_LINK') . '/orders/order_traking/' . $order->id);
    }
}
