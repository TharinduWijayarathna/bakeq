<?php

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Validation\ValidationException;

class MarkOrderBalanceCollected
{
    public function handle(Order $order): Order
    {
        if (! in_array($order->payment_status, [PaymentStatus::Unpaid, PaymentStatus::PartiallyPaid, PaymentStatus::AwaitingPayment], true)) {
            throw ValidationException::withMessages([
                'payment' => 'This order has no outstanding balance to collect.',
            ]);
        }

        $order->update([
            'deposit_paid' => $order->subtotal + $order->addons_total + $order->delivery_fee + $order->tax_amount,
            'total_due' => 0,
            'payment_status' => PaymentStatus::Paid,
            'paid_at' => $order->paid_at ?? now(),
        ]);

        return $order->fresh();
    }
}
