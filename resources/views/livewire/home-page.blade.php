<div class="min-h-screen bg-background">
    <section class="p-3 sm:p-6">
        <div class="relative overflow-hidden rounded-4xl bg-gradient-hero shadow-sweet">
            <div class="grid items-center gap-6 lg:grid-cols-2">
                <div class="relative">
                    <x-lazy-img
                        :src="asset('images/hero-cake.jpg')"
                        alt="Three-tier berry celebration cake with strawberries and gold lights"
                        :eager="true"
                        class="h-[340px] w-full object-cover sm:h-[460px] lg:h-[620px] lg:rounded-br-[8rem]"
                    />
                </div>
                <div class="relative flex min-h-[340px] items-center px-6 py-10 sm:min-h-[460px] sm:px-12 lg:min-h-[620px] lg:px-12 lg:py-16 xl:px-16">
                    <div class="absolute inset-0 dots-pattern opacity-30" aria-hidden="true"></div>
                    <div class="relative w-full max-w-2xl animate-hero-enter">
                        <p class="font-script text-4xl text-primary-foreground/90 sm:text-5xl">Bakeq Cakes by Shashi</p>
                        <h1 class="mt-2 text-5xl leading-[0.92] text-[color:var(--hero-deep)] sm:text-6xl lg:text-7xl xl:text-8xl">
                            sweet cakes<br>made fresh
                        </h1>
                        <p class="mt-6 max-w-xl text-base leading-relaxed text-[color:var(--hero-deep)]/80 sm:text-lg">
                            Birthday cakes, wedding tiers, cupcakes and dessert tables — handcrafted in small batches, decorated by hand and delivered on the day you celebrate.
                        </p>
                        <div class="mt-10 flex flex-wrap items-center gap-3">
                            <a href="{{ route('cakes.index') }}" class="inline-flex items-center gap-2 rounded-full bg-[color:var(--hero-deep)] px-8 py-4 text-sm font-bold text-primary-foreground shadow-sweet transition hover:-translate-y-0.5" wire:navigate>
                                <x-icon name="shopping-bag" class="size-4" /> Order now!
                            </a>
                            <a href="{{ route('designer') }}" class="rounded-full border border-card/60 px-7 py-4 text-sm font-bold text-[color:var(--hero-deep)] transition hover:bg-card/30" wire:navigate>
                                Design a cake
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="menu" class="mx-auto max-w-6xl px-5 py-20">
        <div class="text-center" x-reveal>
            <p class="font-script text-3xl text-primary">Our bakery menu</p>
            <h2 class="mt-1 text-4xl sm:text-5xl">Baked for your moments</h2>
        </div>
        <div class="mt-12 grid gap-6 sm:grid-cols-3" x-reveal.stagger>
            @forelse ($featuredCakes as $cake)
                <article wire:key="featured-{{ $cake->id }}" class="group overflow-hidden rounded-4xl bg-card shadow-soft transition hover:-translate-y-1.5 hover:shadow-sweet">
                    <a href="{{ route('cakes.show', $cake) }}" wire:navigate>
                        <x-lazy-img :src="$cake->imageUrl()" :alt="$cake->name" class="h-64 w-full object-cover transition duration-500 group-hover:scale-105" />
                    </a>
                    <div class="p-6">
                        <h3 class="text-2xl">{{ $cake->name }}</h3>
                        <p class="mt-1 text-sm text-muted-foreground">{{ $cake->note }}</p>
                        <div class="mt-5 flex items-center justify-between">
                            <span class="text-lg font-bold text-primary">{{ $cake->formattedPrice() }}</span>
                            <a href="{{ route('cakes.show', $cake) }}" class="rounded-full bg-secondary px-5 py-2.5 text-xs font-bold uppercase tracking-wider text-secondary-foreground transition hover:bg-primary hover:text-primary-foreground" wire:navigate>
                                View cake
                            </a>
                        </div>
                    </div>
                </article>
            @empty
                <p class="col-span-3 text-center text-muted-foreground">Cakes will appear here once the bakery adds them.</p>
            @endforelse
        </div>
        <div class="mt-10 text-center" x-reveal>
            <a href="{{ route('cakes.index') }}" class="inline-flex items-center gap-2 rounded-full bg-primary px-7 py-3 text-sm font-bold text-primary-foreground shadow-soft transition hover:-translate-y-0.5" wire:navigate>
                Browse all cakes <x-icon name="arrow-right" class="size-4" />
            </a>
        </div>
    </section>

    <section id="gallery" class="bg-secondary/60 py-20">
        <div class="mx-auto max-w-6xl px-5">
            <div class="grid gap-10 lg:grid-cols-[1fr_1.1fr] lg:items-center">
                <div x-reveal.left>
                    <p class="font-script text-3xl text-primary">How ordering works</p>
                    <h2 class="mt-1 text-4xl sm:text-5xl">Three sweet steps</h2>
                    <div class="mt-8 space-y-5" x-reveal.stagger>
                        <div class="flex gap-4 rounded-3xl bg-card p-5 shadow-soft">
                            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary text-primary-foreground"><x-icon name="cake" class="size-5" /></span>
                            <div>
                                <h3 class="text-xl">Pick your cake</h3>
                                <p class="text-sm text-muted-foreground">Choose a flavour, size and a design you love from the menu.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 rounded-3xl bg-card p-5 shadow-soft">
                            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary text-primary-foreground"><x-icon name="sparkle" class="size-5" /></span>
                            <div>
                                <h3 class="text-xl">Customise it</h3>
                                <p class="text-sm text-muted-foreground">Use the designer cards — no typing prompts — then add to cart.</p>
                            </div>
                        </div>
                        <div class="flex gap-4 rounded-3xl bg-card p-5 shadow-soft">
                            <span class="grid size-12 shrink-0 place-items-center rounded-2xl bg-primary text-primary-foreground"><x-icon name="truck" class="size-5" /></span>
                            <div>
                                <h3 class="text-xl">We deliver</h3>
                                <p class="text-sm text-muted-foreground">Baked the same morning and delivered chilled to your door.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4" x-reveal.stagger>
                    <x-lazy-img :src="asset('images/cakes/birthday.jpg')" alt="Pink celebration cake" class="col-span-2 h-64 w-full rounded-4xl object-cover shadow-soft" />
                    <x-lazy-img :src="asset('images/cakes/cupcakes.jpg')" alt="Box of cupcakes" class="h-48 w-full rounded-4xl object-cover shadow-soft" />
                    <x-lazy-img :src="asset('images/cakes/wedding.jpg')" alt="Two tier wedding cake" class="h-48 w-full rounded-4xl object-cover shadow-soft" />
                </div>
            </div>
        </div>
    </section>

    <section class="space-y-6 px-3 py-6 sm:space-y-8 sm:px-6 sm:py-10">
        <div x-reveal.scale>
            <x-lazy-img
                :src="asset('images/brand-banner.jpg')"
                alt="Shashi of Rushq cakes, baked with love, made for you"
                class="h-auto w-full rounded-4xl shadow-soft"
            />
        </div>

        <div id="about" class="rounded-4xl bg-card px-6 py-16 shadow-soft sm:px-16 sm:py-24 lg:px-24 lg:py-28" x-reveal>
            <div class="mx-auto max-w-4xl text-center">
                <p class="font-script text-3xl text-primary sm:text-4xl">About us</p>
                <h2 class="mt-2 text-4xl sm:text-5xl lg:text-6xl">Baked with love in every layer</h2>
                <p class="mx-auto mt-6 max-w-3xl text-base leading-relaxed text-muted-foreground sm:text-lg">
                    Bakeq Cakes by Shashi is a home bakery built on family recipes, fresh ingredients and a love for celebrations. From custom birthday cakes to elegant wedding tiers, every cake is made to order and decorated by hand — so the cake on your table tastes as special as the moment it was made for.
                </p>
            </div>
            <div class="mx-auto mt-12 grid max-w-5xl gap-5 sm:grid-cols-3" x-reveal.stagger>
                <div class="rounded-3xl bg-secondary/70 px-6 py-8 text-center">
                    <h3 class="text-xl">Home bakery</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">Small batches, baked the morning of your celebration — never sitting on a shelf.</p>
                </div>
                <div class="rounded-3xl bg-secondary/70 px-6 py-8 text-center">
                    <h3 class="text-xl">Made to order</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">Flavours, size and design are chosen for your day, then decorated by hand.</p>
                </div>
                <div class="rounded-3xl bg-secondary/70 px-6 py-8 text-center">
                    <h3 class="text-xl">Baked with love</h3>
                    <p class="mt-2 text-sm leading-relaxed text-muted-foreground">Family recipes and careful finishing, from first sponge to the last piped rose.</p>
                </div>
            </div>
        </div>
    </section>

    <x-storefront.testimonials :testimonials="$testimonials" />
</div>
