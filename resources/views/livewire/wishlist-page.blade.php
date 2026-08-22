<div class="mx-auto max-w-5xl px-5 py-12">
    <p class="font-script text-3xl text-primary animate-hero-enter">Saved</p>
    <h1 class="mt-1 text-4xl animate-hero-enter">Wishlist</h1>
    <x-flash />

    @if ($items->isEmpty())
        <p class="mt-8 text-muted-foreground">No saved cakes yet.</p>
    @else
        <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3" x-reveal.stagger>
            @foreach ($items as $item)
                <article wire:key="wish-{{ $item->id }}" class="overflow-hidden rounded-4xl bg-card shadow-soft">
                    <x-lazy-img :src="$item->cake->imageUrl()" :alt="$item->cake->name" class="h-48 w-full object-cover" />
                    <div class="p-5">
                        <h2 class="text-xl">{{ $item->cake->name }}</h2>
                        <p class="mt-1 font-bold text-primary">{{ $item->cake->formattedPrice() }}</p>
                        <div class="mt-4 flex gap-2">
                            <button type="button" wire:click="addToCart({{ $item->cake_id }})" wire:loading.attr="disabled" wire:target="addToCart({{ $item->cake_id }})" class="inline-flex items-center justify-center gap-1.5 rounded-full bg-primary px-4 py-2 text-xs font-bold uppercase text-primary-foreground disabled:opacity-70">
                                <span wire:loading.remove wire:target="addToCart({{ $item->cake_id }})">Add to cart</span>
                                <span wire:loading.flex wire:target="addToCart({{ $item->cake_id }})"><x-spinner class="size-3.5" /></span>
                            </button>
                            <button type="button" wire:click="remove({{ $item->id }})" wire:loading.attr="disabled" wire:target="remove({{ $item->id }})" class="inline-flex items-center justify-center gap-1.5 rounded-full px-4 py-2 text-xs font-bold uppercase text-muted-foreground disabled:opacity-70">
                                <span wire:loading.remove wire:target="remove({{ $item->id }})">Remove</span>
                                <span wire:loading.flex wire:target="remove({{ $item->id }})"><x-spinner class="size-3.5" /></span>
                            </button>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>
