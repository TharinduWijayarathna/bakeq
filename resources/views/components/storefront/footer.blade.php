<footer id="contact" class="px-3 pb-3 sm:px-6 sm:pb-6" x-reveal>
    <div class="rounded-4xl bg-gradient-hero px-6 py-14 text-center shadow-sweet sm:px-12">
        <x-icon name="heart-handshake" class="mx-auto size-10 text-[color:var(--hero-deep)]" />
        <h2 class="mt-4 text-4xl text-[color:var(--hero-deep)] sm:text-5xl">Ready to order your cake?</h2>
        <p class="mx-auto mt-3 max-w-lg text-sm text-[color:var(--hero-deep)]/80">
            Tell us the date, flavour and design. We take custom orders with a short lead time so every cake is baked fresh.
        </p>
        <div class="mt-8 flex flex-wrap justify-center gap-3">
            <a href="{{ route('cakes.index') }}" class="inline-flex items-center gap-2 rounded-full bg-[color:var(--hero-deep)] px-8 py-4 text-sm font-bold text-primary-foreground transition hover:-translate-y-0.5">
                <x-icon name="shopping-bag" class="size-4" /> Browse cakes
            </a>
            <a href="{{ route('designer') }}" class="rounded-full bg-card px-8 py-4 text-sm font-bold text-primary transition hover:-translate-y-0.5">
                Open designer
            </a>
        </div>
        <x-storefront.social-links class="mt-8 justify-center" />
        <div class="mt-12 flex flex-col items-center gap-3 border-t border-card/40 pt-6 text-xs text-[color:var(--hero-deep)]/70 sm:flex-row sm:justify-between">
            <div class="flex items-center gap-2">
                <img src="{{ asset('images/logo.png') }}" alt="Rushq cakes by Shashi" class="h-12 w-auto" loading="lazy" decoding="async">
            </div>
            <span>&copy; {{ now()->year }} Bakeq. Baked with love.</span>
        </div>
    </div>
</footer>
