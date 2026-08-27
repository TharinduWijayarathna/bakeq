<?php

namespace App\Http\Controllers;

use App\Actions\MarkOrderPaidFromIpg;
use App\Contracts\IpgGateway;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;
use Throwable;

class IpgWebhookController extends Controller
{
    public function __invoke(Request $request, IpgGateway $gateway, MarkOrderPaidFromIpg $markPaid): Response
    {
        if (! config('ipg.webhooks_enabled')) {
            return response('Webhooks disabled', 200);
        }

        $signature = (string) (
            $request->header('Stripe-Signature')
            ?: $request->header('X-Ipg-Signature')
            ?: ''
        );

        try {
            $event = $gateway->parseWebhook($request->getContent(), $signature);
        } catch (RuntimeException) {
            return response('Invalid signature', 400);
        }

        if ($event['type'] !== 'checkout.session.completed') {
            return response('Ignored', 200);
        }

        if (($event['payment_status'] ?? null) !== 'paid' && ($event['payment_status'] ?? null) !== null) {
            return response('Ignored unpaid session', 200);
        }

        $order = null;

        if (filled($event['checkout_id'])) {
            $order = Order::query()->where('ipg_checkout_id', $event['checkout_id'])->first();
        }

        if ($order === null && filled($event['order_id'])) {
            $order = Order::query()->find($event['order_id']);
        }

        if ($order === null) {
            return response('Order not found', 404);
        }

        if ($order->payment_status === PaymentStatus::AwaitingPayment || $order->payment_status === PaymentStatus::Failed) {
            try {
                $markPaid->handle($order, [
                    'checkout_id' => $event['checkout_id'],
                    'payment_id' => is_string($event['payment_id'] ?? null) ? $event['payment_id'] : null,
                    'amount_total' => $event['amount_total'],
                ]);
            } catch (Throwable) {
                return response('Unable to mark paid', 500);
            }
        }

        return response('OK', 200);
    }
}
