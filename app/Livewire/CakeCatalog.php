<?php

namespace App\Livewire;

use App\Actions\AddToCart;
use App\Models\Cake;
use App\Models\Category;
use App\Models\WishlistItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.storefront')]
#[Title('Our cakes')]
class CakeCatalog extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = 'all';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function setCategory(string $slug): void
    {
        $this->category = $slug;
        $this->resetPage();
    }

    public function addToCart(int $cakeId, AddToCart $addToCart): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $cake = Cake::query()->active()->findOrFail($cakeId);
        $addToCart->handle(auth()->user(), cake: $cake);

        session()->flash('status', $cake->name.' added to your cart.');
        $this->dispatch('cart-updated');
    }

    public function toggleWishlist(int $cakeId): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $existing = WishlistItem::query()
            ->whereBelongsTo(auth()->user())
            ->where('cake_id', $cakeId)
            ->first();

        if ($existing !== null) {
            $existing->delete();
            session()->flash('status', 'Removed from wishlist.');
        } else {
            WishlistItem::query()->create([
                'user_id' => auth()->id(),
                'cake_id' => $cakeId,
            ]);
            session()->flash('status', 'Saved to wishlist.');
        }

        $this->dispatch('wishlist-updated');
    }

    public function render(): View
    {
        $categories = Category::query()->active()->orderBy('sort')->get();

        $cakes = Cake::query()
            ->active()
            ->with('category')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($searchQuery): void {
                    $searchQuery
                        ->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('note', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->when($this->category !== 'all', function ($query) use ($categories): void {
                $category = $categories->firstWhere('slug', $this->category);
                if ($category !== null) {
                    $query->whereBelongsTo($category);
                }
            })
            ->latest()
            ->paginate(9);

        $wishlistIds = auth()->check()
            ? WishlistItem::query()->whereBelongsTo(auth()->user())->pluck('cake_id')
            : collect();

        return view('livewire.cake-catalog', [
            'categories' => $categories,
            'cakes' => $cakes,
            'wishlistIds' => $wishlistIds,
        ]);
    }
}
