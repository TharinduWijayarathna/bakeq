<header
    class="sticky top-0 z-40 border-b border-border/50 bg-background/90 backdrop-blur transition-shadow duration-300"
    x-data="{ scrolled: false }"
    x-on:scroll.window="scrolled = window.scrollY > 12"
    x-bind:class="scrolled && 'shadow-soft'"
>
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6">
        <a href="{{ route('home') }}" class="flex items-center gap-2.5" wire:navigate>
            <img src="{{ asset('images/logo-mark.png') }}" alt="" class="h-10 w-auto sm:h-11" width="44" height="44" decoding="async">
            <span class="font-script text-2xl leading-none text-primary sm:text-3xl">Rushq cakes</span>
        </a>

        <nav class="hidden items-center gap-6 lg:flex">
            <a href="{{ route('home') }}" class="text-sm font-semibold {{ request()->routeIs('home') ? 'text-primary' : 'text-muted-foreground hover:text-foreground' }}" wire:navigate>Home</a>
            <a href="{{ route('cakes.index') }}" class="text-sm font-semibold {{ request()->routeIs('cakes.*') ? 'text-primary' : 'text-muted-foreground hover:text-foreground' }}" wire:navigate>Cakes</a>
            <a href="{{ route('designer') }}" class="text-sm font-semibold {{ request()->routeIs('designer') ? 'text-primary' : 'text-muted-foreground hover:text-foreground' }}" wire:navigate>Designer</a>
            <a href="{{ route('assistant') }}" class="text-sm font-semibold {{ request()->routeIs('assistant') ? 'text-primary' : 'text-muted-foreground hover:text-foreground' }}" wire:navigate>Assistant</a>
        </nav>

        <div class="flex items-center gap-2">
            <a href="{{ route('wishlist') }}" class="relative grid size-10 place-items-center rounded-full text-muted-foreground transition hover:bg-muted" aria-label="Wishlist" wire:navigate>
                <x-icon name="heart" class="size-5" />
                @if ($wishlistCount > 0)
                    <span class="absolute -right-0.5 -top-0.5 grid size-5 place-items-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground">{{ $wishlistCount }}</span>
                @endif
            </a>
            <a href="{{ route('cart') }}" class="relative grid size-10 place-items-center rounded-full text-muted-foreground transition hover:bg-muted" aria-label="Cart" wire:navigate>
                <x-icon name="shopping-bag" class="size-5" />
                @if ($cartCount > 0)
                    <span class="absolute -right-0.5 -top-0.5 grid size-5 place-items-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground">{{ $cartCount }}</span>
                @endif
            </a>
            @auth
                <a href="{{ route('profile') }}" class="hidden rounded-full bg-secondary px-4 py-2 text-xs font-bold uppercase tracking-wider text-secondary-foreground sm:inline-flex" wire:navigate>
                    {{ auth()->user()->name }}
                </a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" class="hidden rounded-full bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground sm:inline-flex" wire:navigate>
                        Admin
                    </a>
                @endif
            @else
                <a href="{{ route('login') }}" class="rounded-full bg-primary px-4 py-2 text-xs font-bold uppercase tracking-wider text-primary-foreground" wire:navigate>
                    Sign in
                </a>
            @endauth
            <button type="button" wire:click="$toggle('menuOpen')" class="grid size-10 place-items-center rounded-full text-muted-foreground hover:bg-muted lg:hidden" aria-label="Open menu">
                <x-icon :name="$menuOpen ? 'x' : 'menu'" class="size-5" />
            </button>
        </div>
    </div>

    @if ($menuOpen)
        <div class="space-y-1 border-t border-border px-4 py-3 lg:hidden">
            <a href="{{ route('home') }}" class="block rounded-2xl px-3 py-2 text-sm font-semibold" wire:navigate>Home</a>
            <a href="{{ route('cakes.index') }}" class="block rounded-2xl px-3 py-2 text-sm font-semibold" wire:navigate>Cakes</a>
            <a href="{{ route('designer') }}" class="block rounded-2xl px-3 py-2 text-sm font-semibold" wire:navigate>Designer</a>
            <a href="{{ route('assistant') }}" class="block rounded-2xl px-3 py-2 text-sm font-semibold" wire:navigate>Assistant</a>
            @auth
                <a href="{{ route('orders.index') }}" class="block rounded-2xl px-3 py-2 text-sm font-semibold" wire:navigate>My orders</a>
                <a href="{{ route('profile') }}" class="block rounded-2xl px-3 py-2 text-sm font-semibold" wire:navigate>Profile</a>
            @else
                <a href="{{ route('register') }}" class="block rounded-2xl px-3 py-2 text-sm font-semibold" wire:navigate>Create account</a>
            @endauth
            <x-storefront.social-links class="px-3 pt-3" />
        </div>
    @endif
</header>
