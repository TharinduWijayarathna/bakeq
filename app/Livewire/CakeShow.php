<?php

namespace App\Livewire;

use App\Actions\AddToCart;
use App\Models\Cake;
use App\Models\WishlistItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class CakeShow extends Component
{
    public Cake $cake;

    public function mount(Cake $cake): void
    {
        abort_unless($cake->is_active || auth()->user()?->isAdmin(), 404);

        $this->cake = $cake->load('category');
    }

    public function addToCart(AddToCart $addToCart): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $addToCart->handle(auth()->user(), cake: $this->cake);
        session()->flash('status', $this->cake->name.' added to your cart.');
        $this->dispatch('cart-updated');
    }

    public function toggleWishlist(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $existing = WishlistItem::query()
            ->whereBelongsTo(auth()->user())
            ->whereBelongsTo($this->cake)
            ->first();

        if ($existing !== null) {
            $existing->delete();
            session()->flash('status', 'Removed from wishlist.');
        } else {
            WishlistItem::query()->create([
                'user_id' => auth()->id(),
                'cake_id' => $this->cake->id,
            ]);
            session()->flash('status', 'Saved to wishlist.');
        }

        $this->dispatch('wishlist-updated');
    }

    public function render(): View
    {
        $inWishlist = auth()->check()
            && WishlistItem::query()->whereBelongsTo(auth()->user())->whereBelongsTo($this->cake)->exists();

        return view('livewire.cake-show', [
            'inWishlist' => $inWishlist,
        ])->title($this->cake->name);
    }
}
