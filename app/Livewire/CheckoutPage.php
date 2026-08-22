<?php

namespace App\Livewire;

use App\Actions\PlaceOrder;
use App\Models\CartItem;
use App\Models\DesignerSetting;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Checkout')]
class CheckoutPage extends Component
{
    public string $delivery_date = '';

    public string $delivery_address = '';

    public string $notes = '';

    public function mount(): void
    {
        $settings = DesignerSetting::current();
        $user = auth()->user();

        $this->delivery_date = now()->addDays($settings->lead_days)->toDateString();
        $this->delivery_address = trim(($user->address_line ?? '').', '.($user->city ?? ''), ', ');
    }

    public function placeOrder(PlaceOrder $placeOrder): void
    {
        $settings = DesignerSetting::current();

        $validated = $this->validate([
            'delivery_date' => ['required', 'date', 'after_or_equal:'.now()->addDays($settings->lead_days)->toDateString()],
            'delivery_address' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = $placeOrder->handle(auth()->user(), $validated);

        $this->dispatch('cart-updated');
        session()->flash('status', 'Order #'.$order->id.' placed. We will confirm it shortly.');

        $this->redirect(route('orders.index'), navigate: true);
    }

    public function render(): View
    {
        $items = CartItem::query()
            ->whereBelongsTo(auth()->user())
            ->with(['cake', 'cakeDesign'])
            ->get();

        return view('livewire.checkout-page', [
            'items' => $items,
            'total' => Money::format($items->sum(fn (CartItem $item): int => $item->lineTotal())),
            'settings' => DesignerSetting::current(),
        ]);
    }
}
