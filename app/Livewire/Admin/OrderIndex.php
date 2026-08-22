<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.admin')]
#[Title('Orders')]
class OrderIndex extends Component
{
    #[Url]
    public string $status = '';

    public function render(): View
    {
        $orders = Order::query()
            ->with('user')
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->latest()
            ->get();

        return view('livewire.admin.order-index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
        ]);
    }
}
