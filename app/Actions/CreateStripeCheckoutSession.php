<?php

namespace App\Actions;

use App\Contracts\StripeGateway;
use App\Enums\CheckoutPaymentChoice;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class CreateStripeCheckoutSession
{
    public function __construct(private StripeGateway $gateway) {}

    /**
     * @return array{id: string, url: string}
     */
    public function handle(Order $order, CheckoutPaymentChoice $choice, int $amountCents): array
    {
        if (! $choice->isOnline()) {
            throw ValidationException::withMessages([
                'payment_choice' => 'Online payment is required to start checkout.',
            ]);
        }

        if ($order->payment_status !== PaymentStatus::AwaitingPayment) {
            throw ValidationException::withMessages([
                'payment_choice' => 'This order is not awaiting payment.',
            ]);
        }

        if ($amountCents < 1) {
            throw ValidationException::withMessages([
                'payment_choice' => 'Nothing to charge for this order.',
            ]);
        }

        try {
            $session = $this->gateway->createCheckoutSession($order, $amountCents, [
                'success_url' => URL::route('checkout.payment.success', [
                    'order' => $order->id,
                ], absolute: true).'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => URL::route('checkout.payment.cancel', [
                    'order' => $order->id,
                ], absolute: true),
            ]);
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages([
                'payment_choice' => $exception->getMessage(),
            ]);
        }

        $order->update([
            'stripe_checkout_id' => $session['id'],
        ]);

        return $session;
    }
}
