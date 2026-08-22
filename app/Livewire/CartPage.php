<?php

namespace App\Livewire;

use App\Models\CartItem;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Your cart')]
class CartPage extends Component
{
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

        return view('livewire.cart-page', [
            'items' => $items,
            'total' => Money::format($items->sum(fn (CartItem $item): int => $item->lineTotal())),
        ]);
    }

    private function ownedItem(int $itemId): CartItem
    {
        return CartItem::query()
            ->whereBelongsTo(auth()->user())
            ->findOrFail($itemId);
    }
}
