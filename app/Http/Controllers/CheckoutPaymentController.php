<?php

namespace App\Http\Controllers;

use App\Actions\MarkOrderPaidFromStripe;
use App\Contracts\StripeGateway;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class CheckoutPaymentController extends Controller
{
    public function success(
        Request $request,
        Order $order,
        StripeGateway $gateway,
        MarkOrderPaidFromStripe $markPaid,
    ): RedirectResponse {
        abort_unless($order->user_id === $request->user()?->id, 403);

        if ($order->payment_status === PaymentStatus::AwaitingPayment || $order->payment_status === PaymentStatus::Failed) {
            $checkoutId = (string) ($request->query('session_id') ?: $order->stripe_checkout_id);

            if ($checkoutId === '') {
                session()->flash('status', 'Payment for order #'.$order->id.' is still pending confirmation.');

                return redirect()->route('orders.index');
            }

            try {
                $session = $gateway->retrieveCheckoutSession($checkoutId);
            } catch (Throwable) {
                session()->flash('status', 'We could not confirm payment for order #'.$order->id.' yet. Please contact us if you were charged.');

                return redirect()->route('orders.index');
            }

            if (($session['payment_status'] ?? null) === 'paid') {
                $markPaid->handle($order, [
                    'checkout_id' => $session['id'],
                    'payment_id' => $session['payment_id'],
                    'amount_total' => $session['amount_total'] ?? $order->payment_amount,
                ]);

                session()->flash('status', 'Payment received for order #'.$order->id.'. Thank you!');

                return redirect()->route('orders.index');
            }

            session()->flash('status', 'Payment for order #'.$order->id.' is still pending. You can try again or pay later.');

            return redirect()->route('orders.index');
        }

        session()->flash('status', 'Order #'.$order->id.' is already updated. Thank you!');

        return redirect()->route('orders.index');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        abort_unless($order->user_id === $request->user()?->id, 403);

        session()->flash('status', 'Payment was cancelled for order #'.$order->id.'. You can try again or pay later.');

        return redirect()->route('orders.index');
    }
}
