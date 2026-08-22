<?php

namespace App\Livewire\Storefront;

use App\Models\CartItem;
use App\Models\WishlistItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class Header extends Component
{
    public int $cartCount = 0;

    public int $wishlistCount = 0;

    public bool $menuOpen = false;

    public function mount(): void
    {
        $this->refreshCounts();
    }

    #[On('cart-updated')]
    #[On('wishlist-updated')]
    public function refreshCounts(): void
    {
        $user = auth()->user();

        if ($user === null) {
            $this->cartCount = 0;
            $this->wishlistCount = 0;

            return;
        }

        $this->cartCount = (int) CartItem::query()->whereBelongsTo($user)->sum('quantity');
        $this->wishlistCount = WishlistItem::query()->whereBelongsTo($user)->count();
    }

    public function render(): View
    {
        return view('livewire.storefront.header');
    }
}
