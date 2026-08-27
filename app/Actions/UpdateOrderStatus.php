<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Enums\ProductionStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\AuditLogger;

class UpdateOrderStatus
{
    public function __construct(
        private AdjustInventoryForOrder $inventory,
        private GenerateInvoice $generateInvoice,
    ) {}

    public function handle(Order $order, OrderStatus $to, ?User $actor = null): Order
    {
        $from = $order->status;

        if ($from === $to) {
            return $order->loadMissing(['user', 'items', 'invoice']);
        }

        $this->inventory->syncForStatusChange($order, $from, $to);

        $payload = ['status' => $to];

        if ($to === OrderStatus::Delivered) {
            $payload['production_status'] = ProductionStatus::Delivered;
        } elseif ($to === OrderStatus::Baking) {
            $payload['production_status'] = ProductionStatus::Baking;
        } elseif ($to === OrderStatus::Confirmed && $order->production_status === ProductionStatus::Planning) {
            $payload['production_status'] = ProductionStatus::Planning;
        }

        $order->update($payload);
        $this->generateInvoice->handle($order->fresh(['user', 'items']));

        $fresh = $order->fresh(['user', 'items', 'invoice']);

        AuditLogger::record('order.status_changed', $fresh, [
            'status' => $from->value,
        ], [
            'status' => $to->value,
            'production_status' => $fresh->production_status->value,
        ], $actor);

        return $fresh;
    }
}
