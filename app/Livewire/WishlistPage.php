<?php

namespace App\Livewire;

use App\Actions\AddToCart;
use App\Models\Cake;
use App\Models\WishlistItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.storefront')]
#[Title('Wishlist')]
class WishlistPage extends Component
{
    public function remove(int $itemId): void
    {
        WishlistItem::query()
            ->whereBelongsTo(auth()->user())
            ->findOrFail($itemId)
            ->delete();

        $this->dispatch('wishlist-updated');
    }

    public function addToCart(int $cakeId, AddToCart $addToCart): void
    {
        $cake = Cake::query()->active()->findOrFail($cakeId);
        $addToCart->handle(auth()->user(), cake: $cake);
        session()->flash('status', $cake->name.' added to your cart.');
        $this->dispatch('cart-updated');
    }

    public function render(): View
    {
        return view('livewire.wishlist-page', [
            'items' => WishlistItem::query()
                ->whereBelongsTo(auth()->user())
                ->with('cake.category')
                ->latest()
                ->get(),
        ]);
    }
}
