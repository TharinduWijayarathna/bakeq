<?php

namespace App\Livewire;

use App\Actions\AddToCart;
use App\Enums\OrderOrigin;
use App\Jobs\GeneratePromptCakePreview;
use App\Models\Cake;
use App\Models\CakeDesign;
use App\Models\WishlistItem;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.storefront')]
class CakeShow extends Component
{
    public Cake $cake;

    public bool $redesignOpen = false;

    public string $redesignPrompt = '';

    public string $redesignNotes = '';

    public ?int $redesignDesignId = null;

    public bool $redesignGenerating = false;

    public function mount(Cake $cake): void
    {
        abort_unless($cake->is_active || auth()->user()?->isStaff(), 404);

        $this->cake = $cake->load('category');
    }

    public function openRedesign(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $this->redesignOpen = true;
        $this->resetErrorBag('redesign');
    }

    public function generateRedesign(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $this->validate([
            'redesignPrompt' => ['required', 'string', 'min:8', 'max:1000'],
            'redesignNotes' => ['nullable', 'string', 'max:500'],
        ]);

        $referencePath = $this->cake->image_path;
        if (filled($referencePath) && (str_starts_with($referencePath, '/') || str_starts_with($referencePath, 'http'))) {
            $referencePath = null;
        }

        $design = CakeDesign::query()->create([
            'user_id' => auth()->id(),
            'selections' => [
                'mode' => 'redesign',
                'origin' => OrderOrigin::AiRedesign->value,
                'cake_id' => $this->cake->id,
                'cake_name' => $this->cake->name,
                'cake_description' => $this->cake->description,
                'prompt' => trim($this->redesignPrompt),
                'reference_path' => $referencePath,
                'customer_notes' => filled($this->redesignNotes) ? trim($this->redesignNotes) : null,
            ],
            'tiers' => 1,
            'preview_path' => null,
            'estimated_price' => $this->cake->price,
        ]);

        $this->redesignDesignId = $design->id;
        $this->redesignGenerating = true;
        $this->resetErrorBag('redesign');

        GeneratePromptCakePreview::dispatch($design->id);

        $this->refreshRedesignPreview();
    }

    public function refreshRedesignPreview(): void
    {
        if (! $this->redesignGenerating || $this->redesignDesignId === null) {
            return;
        }

        $design = CakeDesign::query()->find($this->redesignDesignId);

        if ($design === null || blank($design->preview_path)) {
            return;
        }

        $this->redesignGenerating = false;

        if (filled(config('services.gemini.key')) && str_starts_with($design->preview_path, 'images/previews/')) {
            $this->addError('redesign', 'The AI redesign took too long, so this is a stand-in. Try again or add notes and continue.');
        }
    }

    public function addRedesignToCart(AddToCart $addToCart): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        $design = CakeDesign::query()->findOrFail($this->redesignDesignId);

        if (blank($design->preview_path)) {
            $this->addError('redesign', 'Wait for the redesign preview to finish first.');

            return;
        }

        if (filled($this->redesignNotes)) {
            $selections = $design->selections ?? [];
            $selections['customer_notes'] = trim($this->redesignNotes);
            $design->update(['selections' => $selections]);
        }

        $addToCart->handle(auth()->user(), design: $design);
        session()->flash('status', 'Redesigned cake added to your cart.');
        $this->dispatch('cart-updated');
        $this->redirect(route('cart'), navigate: true);
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

        $redesignDesign = $this->redesignDesignId
            ? CakeDesign::query()->find($this->redesignDesignId)
            : null;

        return view('livewire.cake-show', [
            'inWishlist' => $inWishlist,
            'redesignDesign' => $redesignDesign,
        ])->title($this->cake->name);
    }
}
