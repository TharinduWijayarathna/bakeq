<?php

namespace App\Livewire;

use App\Models\Order;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Your orders')]
class OrderIndex extends Component
{
    public function render(): View
    {
        return view('livewire.order-index', [
            'orders' => Order::query()
                ->whereBelongsTo(auth()->user())
                ->with('items')
                ->latest()
                ->get(),
        ]);
    }
}
