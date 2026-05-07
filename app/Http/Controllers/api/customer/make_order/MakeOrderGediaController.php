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
    public function callback(Request $request)
    {
        $orderId = $request->query('orderId');

        Log::info('Geidea callback hit', $request->all());

        if (!$orderId) {
            Log::error('Geidea callback: no orderId');
            return redirect(env('WEB_LINK'));
        }

        $settings = Geidia::first();
        config([
            'geidea.merchant_public_key' => $settings->geidea_public_key,
            'geidea.api_password'        => $settings->api_password,
            'geidea.environment'         => $settings->environment,
        ]);

        $orderResult = Geidea::getOrder($orderId);
        Log::info('Geidea getOrder result', $orderResult);

        if (!$orderResult['success']) {
            Log::error('Geidea getOrder failed');
            return redirect(env('WEB_LINK'));
        }

        $merchantReferenceId = $orderResult['order']['merchantReferenceId'] ?? null;
        $localOrderId = null;
        if ($merchantReferenceId && preg_match('/ORDER(\d+)/', $merchantReferenceId, $matches)) {
            $localOrderId = $matches[1];
        }

        Log::info('Geidea localOrderId: ' . $localOrderId);

        $order = Order::where('id', $localOrderId)
                      ->orWhere('transaction_id', $orderId)
                      ->first();

        if (!$order) {
            Log::error('Geidea callback: Order not found', [
                'geidea_order_id'    => $orderId,
                'merchant_reference' => $merchantReferenceId,
                'local_order_id'     => $localOrderId,
            ]);
            return redirect(env('WEB_LINK'));
        }

        $order->update(['transaction_id' => $orderId]);

        $transactions = $orderResult['order']['transactions'] ?? [];
        $successTxn   = collect($transactions)->firstWhere('status', 'Success');
        $paidAmount   = $successTxn['amount'] ?? 0;

        Log::info('Geidea paidAmount: ' . $paidAmount . ' | order amount: ' . $order->amount);

        if ((float)$paidAmount !== (float)$order->amount) {
            Log::critical('Geidea Amount mismatch', [
                'expected' => $order->amount,
                'paid'     => $paidAmount,
                'order_id' => $order->id,
            ]);
            return redirect(env('WEB_LINK'));
        }

        if ($orderResult['order']['status'] === 'Success') {
            if ($order->status === 1) {
                return redirect(env('WEB_LINK') . '/orders/order_traking/' . $order->id);
            }

            $order->update([
                'status'       => 1,
                'order_status' => 'processing',
            ]);

            $user = User::find($order->user_id);
            if ($user) {
                $user->increment('points', $order->points);
            }

            return redirect(env('WEB_LINK') . '/orders/order_traking/' . $order->id);
        }

        Log::warning('Geidea order status not Success: ' . ($orderResult['order']['status'] ?? 'unknown'));
        return redirect(env('WEB_LINK'));
    }

    public function paymentPage(Request $request)
    {
        $settings = Geidia::first();

        config([
            'geidea.merchant_public_key' => $settings->geidea_public_key,
            'geidea.api_password'        => $settings->api_password,
            'geidea.environment'         => $settings->environment,
            'geidea.currency'            => 'EGP',
            'geidea.language'            => 'ar',
        ]);

        return view('Geida.Geida', [
            'sessionId'   => $request->session_id,
            'merchantKey' => $settings->geidea_public_key,
        ]);
    }

    public function return_page(Request $request)
    {
        $geideaOrderId = $request->query('orderId');
        $responseCode  = $request->query('responseCode');
        $sessionId     = $request->query('sessionId');

        Log::info('Geidea return_page', [
            'orderId'      => $geideaOrderId,
            'responseCode' => $responseCode,
            'sessionId'    => $sessionId,
        ]);

        // Cancelled or failed
        if ($responseCode !== '000') {
            return redirect(env('WEB_LINK'));
        }

        // Find order by session_id saved as transaction_id - must be status=2 (pending payment)
        $order = Order::where('transaction_id', $sessionId)
                      ->orWhere('transaction_id', $geideaOrderId)
                      ->where('status', 2)
                      ->first();

        if (!$order) {
            Log::error('Geidea return_page: Order not found', [
                'geideaOrderId' => $geideaOrderId,
                'sessionId'     => $sessionId,
            ]);
            return redirect(env('WEB_LINK'));
        }

        // Already paid
        if ($order->status === 1) {
            return redirect(env('WEB_LINK') . '/orders/order_traking/' . $order->id);
        }

        // Verify payment with Geidea
        $settings = Geidia::first();
        config([
            'geidea.merchant_public_key' => $settings->geidea_public_key,
            'geidea.api_password'        => $settings->api_password,
            'geidea.environment'         => $settings->environment,
        ]);

        try {
            $orderResult = Geidea::getOrder($geideaOrderId);
            Log::info('Geidea return_page getOrder', $orderResult);
        } catch (\Exception $e) {
            Log::error('Geidea return_page getOrder failed: ' . $e->getMessage());
            return redirect(env('WEB_LINK'));
        }

        if (!$orderResult['success'] || ($orderResult['order']['status'] ?? '') !== 'Success') {
            Log::warning('Geidea payment not successful', [
                'status' => $orderResult['order']['status'] ?? 'unknown'
            ]);
            return redirect(env('WEB_LINK'));
        }

        // Verify amount
        $transactions = $orderResult['order']['transactions'] ?? [];
        $successTxn   = collect($transactions)->firstWhere('status', 'Success');
        $paidAmount   = $successTxn['amount'] ?? 0;

        if ((float)$paidAmount !== (float)$order->amount) {
            Log::critical('Geidea Amount mismatch', [
                'expected' => $order->amount,
                'paid'     => $paidAmount,
            ]);
            return redirect(env('WEB_LINK'));
        }

        // Update order
        $order->update([
            'status'         => 1,
            'order_status'   => 'processing',
            'transaction_id' => $geideaOrderId,
        ]);

        $user = User::find($order->user_id);
        if ($user) {
            $user->increment('points', $order->points);
        }

        return redirect(env('WEB_LINK') . '/orders/order_traking/' . $order->id);
    }
}
