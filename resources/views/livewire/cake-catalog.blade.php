<div>
    <section class="relative overflow-hidden bg-gradient-hero">
        <div class="mx-auto grid max-w-6xl gap-8 px-5 py-14 lg:grid-cols-2 lg:items-center lg:py-20">
            <div class="relative animate-hero-enter">
                <div class="absolute inset-0 dots-pattern opacity-20" aria-hidden="true"></div>
                <div class="relative">
                    <a href="{{ route('home') }}" class="mb-4 inline-flex items-center gap-1 text-sm font-semibold text-[color:var(--hero-deep)]/80" wire:navigate>
                        <x-icon name="arrow-left" class="size-4" /> Back to home
                    </a>
                    <p class="font-script text-3xl text-primary-foreground/90 sm:text-4xl">Made to order</p>
                    <h1 class="mt-2 text-4xl leading-[0.95] text-[color:var(--hero-deep)] sm:text-5xl lg:text-6xl">Explore our cake collection</h1>
                    <p class="mt-5 max-w-md text-sm leading-relaxed text-[color:var(--hero-deep)]/80 sm:text-base">
                        From classic birthday cakes to show-stopping wedding tiers, every cake is baked fresh, decorated by hand and tailored to your celebration.
                    </p>
                </div>
            </div>
            <div class="relative animate-fade-in">
                <x-lazy-img :src="asset('images/hero-cake.jpg')" alt="Three-tier berry celebration cake with strawberries and gold lights" :eager="true" class="h-72 w-full rounded-4xl object-cover shadow-sweet sm:h-96 lg:h-[500px]" />
                <div class="absolute bottom-4 left-4 rounded-2xl bg-card/90 px-4 py-3 shadow-soft backdrop-blur">
                    <p class="text-xs font-semibold text-muted-foreground">Signature cake</p>
                    <p class="font-display text-lg font-bold">Berry Celebration Cake</p>
                </div>
            </div>
        </div>
    </section>

    <section id="menu" class="mx-auto max-w-6xl px-5 py-16">
        <x-flash />
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between" x-reveal>
            <div>
                <p class="font-script text-3xl text-primary">Our menu</p>
                <h2 class="mt-1 text-4xl sm:text-5xl">Pick your favourite</h2>
            </div>
            <label class="relative w-full sm:max-w-xs">
                <x-icon name="search" class="absolute left-4 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search cakes" class="w-full rounded-full border border-border bg-card py-3 pl-11 pr-4 text-sm outline-none ring-ring focus:ring-2">
                <span wire:loading.flex wire:target="search" class="absolute right-4 top-1/2 -translate-y-1/2 text-primary">
                    <x-spinner />
                </span>
            </label>
        </div>

        <div class="mt-8 flex flex-wrap gap-2" x-reveal>
            <button type="button" wire:click="setCategory('all')" wire:loading.attr="disabled" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $category === 'all' ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card hover:border-primary hover:text-primary' }}">
                All
            </button>
            @foreach ($categories as $item)
                <button type="button" wire:key="cat-{{ $item->id }}" wire:click="setCategory('{{ $item->slug }}')" wire:loading.attr="disabled" class="rounded-full border px-4 py-2 text-sm font-semibold {{ $category === $item->slug ? 'border-primary bg-primary text-primary-foreground' : 'border-border bg-card hover:border-primary hover:text-primary' }}">
                    {{ $item->name }}
                </button>
            @endforeach
        </div>

        <div class="mt-4 flex items-center gap-2 text-sm text-muted-foreground">
            <x-icon name="filter" class="size-4" />
            <span>{{ $cakes->total() }} cakes available</span>
        </div>

        <div wire:loading.delay wire:target="search,setCategory,gotoPage,nextPage,previousPage">
            <x-storefront.cake-skeleton class="mt-10" />
        </div>

        <div
            class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
            x-reveal.stagger
            wire:loading.delay.class="hidden"
            wire:target="search,setCategory,gotoPage,nextPage,previousPage"
        >
            @forelse ($cakes as $cake)
                <article wire:key="cake-{{ $cake->id }}" class="group flex flex-col overflow-hidden rounded-4xl bg-card shadow-soft transition hover:-translate-y-1.5 hover:shadow-sweet">
                    <div class="relative">
                        <a href="{{ route('cakes.show', $cake) }}" wire:navigate>
                            <x-lazy-img :src="$cake->imageUrl()" :alt="$cake->name" class="h-64 w-full object-cover transition duration-500 group-hover:scale-105" />
                        </a>
                        <span class="absolute left-4 top-4 rounded-full bg-card/90 px-3 py-1 text-xs font-bold uppercase tracking-wider shadow-soft">{{ $cake->category->name }}</span>
                        <button type="button" wire:click="toggleWishlist({{ $cake->id }})" wire:loading.attr="disabled" wire:target="toggleWishlist({{ $cake->id }})" class="absolute right-4 top-4 grid size-10 place-items-center rounded-full bg-card/90 shadow-soft disabled:opacity-70" aria-label="Toggle wishlist">
                            <span wire:loading.remove wire:target="toggleWishlist({{ $cake->id }})">
                                <x-icon name="heart" class="size-4 {{ $wishlistIds->contains($cake->id) ? 'fill-current text-primary' : '' }}" />
                            </span>
                            <span wire:loading.flex wire:target="toggleWishlist({{ $cake->id }})" class="text-primary">
                                <x-spinner class="size-3.5" />
                            </span>
                        </button>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <h3 class="text-2xl leading-tight">{{ $cake->name }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">{{ $cake->note }}</p>
                        <p class="mt-3 text-xs font-semibold text-muted-foreground">Serves {{ $cake->serves }}</p>
                        <div class="mt-auto flex items-center justify-between pt-5">
                            <span class="text-xl font-bold text-primary">{{ $cake->formattedPrice() }}</span>
                            <button type="button" wire:click="addToCart({{ $cake->id }})" wire:loading.attr="disabled" wire:target="addToCart({{ $cake->id }})" class="inline-flex min-w-20 items-center justify-center gap-1.5 rounded-full bg-secondary px-4 py-2 text-xs font-bold uppercase tracking-wider text-secondary-foreground transition hover:bg-primary hover:text-primary-foreground disabled:opacity-70">
                                <span wire:loading.remove wire:target="addToCart({{ $cake->id }})" class="inline-flex items-center gap-1.5">
                                    <x-icon name="shopping-bag" class="size-3.5" /> Add
                                </span>
                                <span wire:loading.flex wire:target="addToCart({{ $cake->id }})">
                                    <x-spinner class="size-3.5" />
                                </span>
                            </button>
                        </div>
                    </div>
                </article>
            @empty
                <p class="col-span-3 text-center text-muted-foreground">No cakes match those filters.</p>
            @endforelse
        </div>

        <div class="mt-10">{{ $cakes->links() }}</div>
    </section>
</div>
