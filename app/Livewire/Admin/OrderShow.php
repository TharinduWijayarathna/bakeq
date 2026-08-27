<?php

namespace App\Livewire\Admin;

use App\Actions\AdjustInventoryForOrder;
use App\Actions\GenerateInvoice;
use App\Actions\MarkOrderBalanceCollected;
use App\Enums\OrderStatus;
use App\Enums\ProductionStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class OrderShow extends Component
{
    public Order $order;

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order->load(['user', 'items', 'invoice']);
    }

    public function updateStatus(string $status, AdjustInventoryForOrder $inventory, GenerateInvoice $generateInvoice): void
    {
        $this->authorize('update', $this->order);

        $from = $this->order->status;
        $to = OrderStatus::from($status);

        if ($from === $to) {
            return;
        }

        try {
            $inventory->syncForStatusChange($this->order, $from, $to);
        } catch (InsufficientStockException $exception) {
            $this->addError('status', $exception->getMessage());

            return;
        }

        $payload = ['status' => $to];

        if ($to === OrderStatus::Delivered) {
            $payload['production_status'] = ProductionStatus::Delivered;
        } elseif ($to === OrderStatus::Baking) {
            $payload['production_status'] = ProductionStatus::Baking;
        } elseif ($to === OrderStatus::Confirmed && $this->order->production_status === ProductionStatus::Planning) {
            $payload['production_status'] = ProductionStatus::Planning;
        }

        $this->order->update($payload);
        $generateInvoice->handle($this->order->fresh(['user', 'items']));

        AuditLogger::record('order.status_changed', $this->order, [
            'status' => $from->value,
        ], [
            'status' => $to->value,
            'production_status' => $this->order->fresh()->production_status->value,
        ]);

        $this->order->refresh()->load(['user', 'items', 'invoice']);
        $this->resetErrorBag('status');
        session()->flash('status', 'Order status updated.');
    }

    public function markBalanceCollected(MarkOrderBalanceCollected $markCollected): void
    {
        $this->authorize('update', $this->order);

        $markCollected->handle($this->order);
        $this->order->refresh()->load(['user', 'items', 'invoice']);

        AuditLogger::record('order.balance_collected', $this->order, [
            'payment_status' => $this->order->payment_status->value,
        ], [
            'payment_status' => 'paid',
            'total_due' => 0,
        ]);

        session()->flash('status', 'Balance marked as collected.');
    }

    public function render(): View
    {
        return view('livewire.admin.order-show', [
            'statuses' => OrderStatus::cases(),
            'formattedPaymentAmount' => Money::format($this->order->payment_amount),
        ])->title('Order #'.$this->order->id);
    }
}
