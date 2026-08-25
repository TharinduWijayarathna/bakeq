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
            @if ($cake->note)
                <p class="mt-2 text-sm font-semibold text-muted-foreground">{{ $cake->note }}</p>
            @endif
            <p class="mt-6 text-3xl font-bold text-primary">{{ $cake->formattedPrice() }}</p>
            <p class="mt-1 text-sm text-muted-foreground">Lead time: {{ $cake->lead_days }} day{{ $cake->lead_days === 1 ? '' : 's' }} · Serves {{ $cake->serves ?: '—' }}</p>

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

    <div class="mt-12 grid gap-6 lg:grid-cols-2">
        <section class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-xl font-bold">Price breakdown</h2>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Base price</dt><dd class="font-semibold">{{ $cake->formattedBasePrice() }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Per-tier add-on</dt><dd class="font-semibold">{{ $cake->formattedPerTierAddon() }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted-foreground">Per-flavour add-on</dt><dd class="font-semibold">{{ $cake->formattedPerFlavorAddon() }}</dd></div>
                <div class="flex justify-between gap-4 border-t border-border pt-2"><dt class="font-semibold">Catalog price</dt><dd class="font-bold text-primary">{{ $cake->formattedPrice() }}</dd></div>
            </dl>

            @if ($cake->optionalAddonRows()->isNotEmpty())
                <h3 class="mt-6 text-sm font-bold uppercase tracking-wider text-muted-foreground">Optional add-ons</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($cake->optionalAddonRows() as $addon)
                        <li wire:key="addon-{{ $loop->index }}" class="flex justify-between gap-4">
                            <span>{{ $addon['name'] }}</span>
                            <span class="font-semibold">+ {{ $addon['formatted_price'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-xl font-bold">Sizes & servings</h2>
            @if ($cake->sizeOptionRows()->isNotEmpty())
                <ul class="mt-4 space-y-3 text-sm">
                    @foreach ($cake->sizeOptionRows() as $size)
                        <li wire:key="size-{{ $loop->index }}" class="rounded-2xl bg-muted/60 px-4 py-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="font-semibold">{{ $size['label'] }}</span>
                                <span class="font-bold text-primary">{{ $size['formatted_price'] }}</span>
                            </div>
                            @if ($size['servings'] !== '')
                                <p class="mt-1 text-muted-foreground">Serves {{ $size['servings'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-sm text-muted-foreground">Serves {{ $cake->serves ?: '—' }}</p>
            @endif
        </section>

        <section class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-xl font-bold">Ingredients</h2>
            @if (! empty($cake->ingredients))
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ($cake->ingredients as $ingredient)
                        <li wire:key="ing-{{ $loop->index }}" class="rounded-full bg-secondary px-3 py-1 text-xs font-semibold text-secondary-foreground">{{ $ingredient }}</li>
                    @endforeach
                </ul>
            @else
                <p class="mt-4 text-sm text-muted-foreground">Ingredients available on request.</p>
            @endif

            <h3 class="mt-6 text-sm font-bold uppercase tracking-wider text-muted-foreground">Allergens</h3>
            @if (! empty($cake->allergens))
                <ul class="mt-3 flex flex-wrap gap-2">
                    @foreach ($cake->allergens as $allergen)
                        <li wire:key="all-{{ $loop->index }}" class="rounded-full bg-destructive/10 px-3 py-1 text-xs font-semibold text-destructive">{{ $allergen }}</li>
                    @endforeach
                </ul>
            @else
                <p class="mt-3 text-sm text-muted-foreground">No allergen tags listed.</p>
            @endif
        </section>

        <section class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-xl font-bold">Care instructions</h2>
            <p class="mt-4 text-sm leading-relaxed text-muted-foreground">{{ $cake->care_instructions ?: 'Store in a cool place. Keep refrigerated for cream cakes. Best enjoyed within 48 hours.' }}</p>
        </section>
    </div>
</div>
