<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
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
        $this->order = $order->load(['user', 'items']);
    }

    public function updateStatus(string $status): void
    {
        $this->authorize('update', $this->order);

        $this->order->update([
            'status' => OrderStatus::from($status),
        ]);

        $this->order->refresh()->load(['user', 'items']);
        session()->flash('status', 'Order status updated.');
    }

    public function render(): View
    {
        return view('livewire.admin.order-show', [
            'statuses' => OrderStatus::cases(),
        ])->title('Order #'.$this->order->id);
    }
}
