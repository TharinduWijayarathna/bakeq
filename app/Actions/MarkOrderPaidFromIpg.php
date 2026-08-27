<?php

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class MarkOrderPaidFromIpg
{
    /**
     * @param  array{
     *     checkout_id?: string|null,
     *     payment_id?: string|null,
     *     amount_total?: int|null
     * }  $payload
     */
    public function handle(Order $order, array $payload = []): Order
    {
        return DB::transaction(function () use ($order, $payload): Order {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->payment_status, [PaymentStatus::Paid, PaymentStatus::PartiallyPaid], true)
                && filled($locked->ipg_payment_id)) {
                return $locked;
            }

            $gross = $locked->subtotal + $locked->addons_total + $locked->delivery_fee + $locked->tax_amount;
            $charged = (int) ($payload['amount_total'] ?? $locked->payment_amount);

            if ($charged < 1) {
                $charged = $locked->payment_amount > 0 ? $locked->payment_amount : $gross;
            }

            $charged = min($charged, $gross);
            $remaining = max(0, $gross - $charged);
            $status = $remaining === 0 ? PaymentStatus::Paid : PaymentStatus::PartiallyPaid;

            $locked->update([
                'deposit_paid' => $charged,
                'total_due' => $remaining,
                'payment_amount' => $charged,
                'payment_status' => $status,
                'ipg_checkout_id' => $payload['checkout_id'] ?? $locked->ipg_checkout_id,
                'ipg_payment_id' => $payload['payment_id'] ?? $locked->ipg_payment_id,
                'paid_at' => $locked->paid_at ?? now(),
            ]);

            return $locked->fresh();
        });
    }
}
