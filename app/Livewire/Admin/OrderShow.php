<?php

namespace App\Livewire\Admin;

use App\Actions\MarkOrderBalanceCollected;
use App\Actions\UpdateOrderPaymentStatus;
use App\Actions\UpdateOrderStatus;
use App\Enums\FulfillmentMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\InsufficientStockException;
use App\Models\Order;
use App\Support\AuditLogger;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class OrderShow extends Component
{
    public Order $order;

    public string $delivery_date = '';

    public string $delivery_address = '';

    public string $notes = '';

    public string $fulfillment_method = 'pickup';

    public function mount(Order $order): void
    {
        $this->authorize('view', $order);
        $this->order = $order->load(['user', 'items', 'invoice']);
        $this->fillFromOrder();
    }

    public function updateStatus(string $status, UpdateOrderStatus $updateStatus): void
    {
        $this->authorize('update', $this->order);

        try {
            $this->order = $updateStatus->handle($this->order, OrderStatus::from($status));
        } catch (InsufficientStockException $exception) {
            $this->addError('status', $exception->getMessage());

            return;
        }

        $this->fillFromOrder();
        $this->resetErrorBag('status');
        session()->flash('status', 'Order status updated.');
    }

    public function saveDetails(): void
    {
        $this->authorize('update', $this->order);

        $validated = $this->validate([
            'delivery_date' => ['required', 'date'],
            'delivery_address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'fulfillment_method' => ['required', Rule::enum(FulfillmentMethod::class)],
        ]);

        $before = [
            'delivery_date' => $this->order->delivery_date->toDateString(),
            'delivery_address' => $this->order->delivery_address,
            'notes' => $this->order->notes,
            'fulfillment_method' => $this->order->fulfillment_method->value,
        ];

        $this->order->update([
            'delivery_date' => $validated['delivery_date'],
            'delivery_address' => $validated['delivery_address'],
            'notes' => filled($validated['notes'] ?? null) ? $validated['notes'] : null,
            'fulfillment_method' => $validated['fulfillment_method'],
        ]);

        $this->order->refresh()->load(['user', 'items', 'invoice']);
        $this->fillFromOrder();

        AuditLogger::record('order.details_updated', $this->order, $before, [
            'delivery_date' => $this->order->delivery_date->toDateString(),
            'delivery_address' => $this->order->delivery_address,
            'notes' => $this->order->notes,
            'fulfillment_method' => $this->order->fulfillment_method->value,
        ]);

        session()->flash('status', 'Order details saved.');
    }

    public function updatePaymentStatus(string $status, UpdateOrderPaymentStatus $updatePaymentStatus): void
    {
        $this->authorize('update', $this->order);

        $this->order = $updatePaymentStatus->handle($this->order, PaymentStatus::from($status));
        $this->fillFromOrder();
        $this->resetErrorBag('payment');
        session()->flash('status', 'Payment status updated.');
    }

    public function markBalanceCollected(MarkOrderBalanceCollected $markCollected): void
    {
        $this->authorize('update', $this->order);

        $markCollected->handle($this->order);
        $this->order->refresh()->load(['user', 'items', 'invoice']);
        $this->fillFromOrder();

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
            'paymentStatuses' => PaymentStatus::cases(),
            'fulfillmentMethods' => FulfillmentMethod::cases(),
            'formattedPaymentAmount' => Money::format($this->order->payment_amount),
        ])->title('Order #'.$this->order->id);
    }

    private function fillFromOrder(): void
    {
        $this->delivery_date = $this->order->delivery_date->toDateString();
        $this->delivery_address = $this->order->delivery_address;
        $this->notes = (string) ($this->order->notes ?? '');
        $this->fulfillment_method = $this->order->fulfillment_method->value;
    }
}
