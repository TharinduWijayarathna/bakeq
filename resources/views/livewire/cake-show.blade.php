<div class="mx-auto max-w-6xl px-5 py-12">
    <a href="{{ route('cakes.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-muted-foreground" wire:navigate>
        <x-icon name="arrow-left" class="size-4" /> All cakes
    </a>
    <x-flash />
    <div class="mt-6 grid gap-10 lg:grid-cols-2">
        <x-lazy-img :src="$cake->imageUrl()" :alt="$cake->name" :eager="true" class="h-[420px] w-full rounded-4xl object-cover shadow-sweet" />
        <div class="animate-hero-enter">
            <span class="rounded-full bg-secondary px-3 py-1 text-xs font-bold uppercase tracking-wider text-secondary-foreground">{{ $cake->category->name }}</span>
            <h1 class="mt-4 text-4xl sm:text-5xl">{{ $cake->name }}</h1>
            <p class="mt-3 text-muted-foreground">{{ $cake->description }}</p>
            <p class="mt-2 text-sm font-semibold text-muted-foreground">{{ $cake->note }} · Serves {{ $cake->serves }}</p>
            <p class="mt-6 text-3xl font-bold text-primary">{{ $cake->formattedPrice() }}</p>
            <div class="mt-8 flex flex-wrap gap-3">
                <button type="button" wire:click="addToCart" wire:loading.attr="disabled" wire:target="addToCart" class="inline-flex items-center justify-center gap-2 rounded-full bg-primary px-7 py-3.5 text-sm font-bold text-primary-foreground disabled:opacity-70">
                    <span wire:loading.remove wire:target="addToCart" class="inline-flex items-center gap-2">
                        <x-icon name="shopping-bag" class="size-4" /> Add to cart
                    </span>
                    <span wire:loading.flex wire:target="addToCart" class="items-center gap-2">
                        <x-spinner /> Adding…
                    </span>
                </button>
                <button type="button" wire:click="toggleWishlist" wire:loading.attr="disabled" wire:target="toggleWishlist" class="inline-flex items-center justify-center gap-2 rounded-full border border-border px-7 py-3.5 text-sm font-bold disabled:opacity-70">
                    <span wire:loading.remove wire:target="toggleWishlist" class="inline-flex items-center gap-2">
                        <x-icon name="heart" class="size-4 {{ $inWishlist ? 'fill-current text-primary' : '' }}" /> Wishlist
                    </span>
                    <span wire:loading.flex wire:target="toggleWishlist" class="items-center gap-2">
                        <x-spinner /> Saving…
                    </span>
                </button>
                <a href="{{ route('designer') }}" class="rounded-full bg-secondary px-7 py-3.5 text-sm font-bold text-secondary-foreground" wire:navigate>Customise in designer</a>
            </div>
        </div>
    </div>
</div>
