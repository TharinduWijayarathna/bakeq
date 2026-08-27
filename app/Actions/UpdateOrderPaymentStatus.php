<?php

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\AuditLogger;

class UpdateOrderPaymentStatus
{
    public function handle(Order $order, PaymentStatus $to, ?User $actor = null): Order
    {
        $from = $order->payment_status;

        if ($from === $to) {
            return $order->loadMissing(['user', 'items', 'invoice']);
        }

        $before = [
            'payment_status' => $from->value,
            'deposit_paid' => $order->deposit_paid,
            'total_due' => $order->total_due,
        ];

        $gross = $order->subtotal + $order->addons_total + $order->delivery_fee + $order->tax_amount;
        $alreadyPaid = max($order->payment_amount, $order->deposit_paid);

        $payload = match ($to) {
            PaymentStatus::Paid => [
                'payment_status' => PaymentStatus::Paid,
                'deposit_paid' => $gross,
                'total_due' => 0,
                'paid_at' => $order->paid_at ?? now(),
            ],
            PaymentStatus::Unpaid => [
                'payment_status' => PaymentStatus::Unpaid,
                'deposit_paid' => 0,
                'total_due' => $gross,
                'paid_at' => null,
            ],
            PaymentStatus::PartiallyPaid => [
                'payment_status' => PaymentStatus::PartiallyPaid,
                'deposit_paid' => min($gross, max($alreadyPaid, 1)),
                'total_due' => max(0, $gross - min($gross, max($alreadyPaid, 1))),
                'paid_at' => $order->paid_at,
            ],
            PaymentStatus::AwaitingPayment => [
                'payment_status' => PaymentStatus::AwaitingPayment,
                'paid_at' => null,
            ],
            PaymentStatus::Failed => [
                'payment_status' => PaymentStatus::Failed,
                'paid_at' => null,
            ],
        };

        $order->update($payload);

        $fresh = $order->fresh(['user', 'items', 'invoice']);

        AuditLogger::record('order.payment_status_changed', $fresh, $before, [
            'payment_status' => $to->value,
            'deposit_paid' => $fresh->deposit_paid,
            'total_due' => $fresh->total_due,
        ], $actor);

        return $fresh;
    }
}
