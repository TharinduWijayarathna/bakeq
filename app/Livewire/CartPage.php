<?php

namespace App\Livewire;

use App\Enums\FulfillmentMethod;
use App\Models\CartItem;
use App\Models\ShopSetting;
use App\Support\Money;
use App\Support\OrderTotals;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Your cart')]
class CartPage extends Component
{
    public string $fulfillment_method = 'delivery';

    public function increment(int $itemId): void
    {
        $item = $this->ownedItem($itemId);
        $item->increment('quantity');
        $this->dispatch('cart-updated');
    }

    public function decrement(int $itemId): void
    {
        $item = $this->ownedItem($itemId);

        if ($item->quantity <= 1) {
            $item->delete();
        } else {
            $item->decrement('quantity');
        }

        $this->dispatch('cart-updated');
    }

    public function remove(int $itemId): void
    {
        $this->ownedItem($itemId)->delete();
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        $items = CartItem::query()
            ->whereBelongsTo(auth()->user())
            ->with(['cake.category', 'cakeDesign'])
            ->latest()
            ->get();

        $itemsSubtotal = $items->sum(fn (CartItem $item): int => $item->lineTotal());
        $fulfillment = FulfillmentMethod::tryFrom($this->fulfillment_method) ?? FulfillmentMethod::Delivery;
        $totals = OrderTotals::calculate($itemsSubtotal, fulfillment: $fulfillment);

        return view('livewire.cart-page', [
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
            'shop' => ShopSetting::current(),
            'fulfillmentMethods' => FulfillmentMethod::cases(),
        ]);
    }

    private function ownedItem(int $itemId): CartItem
    {
        return CartItem::query()
            ->whereBelongsTo(auth()->user())
            ->findOrFail($itemId);
    }
}
