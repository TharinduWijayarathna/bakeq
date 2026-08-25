<?php

namespace App\Livewire;

use App\Actions\PlaceOrder;
use App\Enums\FulfillmentMethod;
use App\Models\CartItem;
use App\Models\DesignerSetting;
use App\Models\ShopSetting;
use App\Support\Money;
use App\Support\OrderTotals;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
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

    public string $fulfillment_method = 'delivery';

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
            'fulfillment_method' => ['required', Rule::enum(FulfillmentMethod::class)],
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

        $itemsSubtotal = $items->sum(fn (CartItem $item): int => $item->lineTotal());
        $fulfillment = FulfillmentMethod::tryFrom($this->fulfillment_method) ?? FulfillmentMethod::Delivery;
        $totals = OrderTotals::calculate($itemsSubtotal, fulfillment: $fulfillment);

        return view('livewire.checkout-page', [
            'items' => $items,
            'totals' => $totals,
            'formatted' => [
                'subtotal' => Money::format($totals['subtotal']),
                'addons_total' => Money::format($totals['addons_total']),
                'delivery_fee' => Money::format($totals['delivery_fee']),
                'tax_amount' => Money::format($totals['tax_amount']),
                'deposit_paid' => Money::format($totals['deposit_paid']),
                'total_due' => Money::format($totals['total_due']),
            ],
            'settings' => DesignerSetting::current(),
            'shop' => ShopSetting::current(),
            'fulfillmentMethods' => FulfillmentMethod::cases(),
        ]);
    }
}
