<div class="mx-auto max-w-4xl px-5 py-12">
    <p class="font-script text-3xl text-primary animate-hero-enter">Cart</p>
    <h1 class="mt-1 text-4xl animate-hero-enter">Your order</h1>
    <x-flash />

    @if ($items->isEmpty())
        <p class="mt-8 text-muted-foreground">Your cart is empty.</p>
        <a href="{{ route('cakes.index') }}" class="mt-4 inline-flex rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground" wire:navigate>Browse cakes</a>
    @else
        <div class="mt-8 space-y-4" x-reveal.stagger>
            @foreach ($items as $item)
                <article wire:key="cart-{{ $item->id }}" class="flex flex-col gap-4 rounded-4xl bg-card p-4 shadow-soft sm:flex-row sm:items-center">
                    @if ($item->cake)
                        <x-lazy-img :src="$item->cake->imageUrl()" alt="" class="h-28 w-full rounded-3xl object-cover sm:w-28" />
                    @elseif ($item->cakeDesign)
                        <x-lazy-img :src="$item->cakeDesign->previewUrl()" alt="" class="h-28 w-full rounded-3xl object-cover sm:w-28" />
                    @endif
                    <div class="flex-1">
                        <h2 class="text-xl">{{ $item->displayName() }}</h2>
                        <p class="text-sm text-primary font-bold">{{ \App\Support\Money::format($item->unit_price) }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="decrement({{ $item->id }})" wire:loading.attr="disabled" wire:target="decrement({{ $item->id }})" class="grid size-9 place-items-center rounded-full bg-muted disabled:opacity-70" aria-label="Decrease quantity">
                            <span wire:loading.remove wire:target="decrement({{ $item->id }})"><x-icon name="minus" /></span>
                            <span wire:loading.flex wire:target="decrement({{ $item->id }})"><x-spinner class="size-3.5" /></span>
                        </button>
                        <span class="w-8 text-center font-bold">{{ $item->quantity }}</span>
                        <button type="button" wire:click="increment({{ $item->id }})" wire:loading.attr="disabled" wire:target="increment({{ $item->id }})" class="grid size-9 place-items-center rounded-full bg-muted disabled:opacity-70" aria-label="Increase quantity">
                            <span wire:loading.remove wire:target="increment({{ $item->id }})"><x-icon name="plus" /></span>
                            <span wire:loading.flex wire:target="increment({{ $item->id }})"><x-spinner class="size-3.5" /></span>
                        </button>
                        <button type="button" wire:click="remove({{ $item->id }})" wire:loading.attr="disabled" wire:target="remove({{ $item->id }})" class="ml-2 grid size-9 place-items-center rounded-full text-destructive disabled:opacity-70" aria-label="Remove">
                            <span wire:loading.remove wire:target="remove({{ $item->id }})"><x-icon name="trash" /></span>
                            <span wire:loading.flex wire:target="remove({{ $item->id }})"><x-spinner class="size-3.5" /></span>
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="mt-8 flex items-center justify-between rounded-4xl bg-secondary p-6">
            <span class="text-lg font-bold">Total</span>
            <span class="text-2xl font-bold text-primary">{{ $total }}</span>
        </div>
        <a href="{{ route('checkout') }}" class="mt-6 inline-flex rounded-full bg-primary px-8 py-4 text-sm font-bold text-primary-foreground" wire:navigate>Checkout</a>
    @endif
</div>
