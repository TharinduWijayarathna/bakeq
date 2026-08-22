<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Admin' }} · Bakeq</title>
        <x-favicons />
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="admin-app min-h-screen bg-background text-foreground antialiased">
        <div class="flex min-h-screen">
            <aside class="hidden w-64 shrink-0 border-r border-border bg-card lg:flex lg:flex-col">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-5 py-5">
                    <img src="{{ asset('images/logo-mark.png') }}" alt="" class="size-10 object-contain">
                    <div>
                        <p class="font-display text-lg font-bold leading-tight">Bakeq</p>
                        <p class="text-xs text-muted-foreground">Admin</p>
                    </div>
                </a>
                <nav class="flex flex-1 flex-col gap-1 px-3 py-2 text-sm font-semibold">
                    <x-admin.nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="layout">Dashboard</x-admin.nav-link>
                    <x-admin.nav-link :href="route('admin.categories')" :active="request()->routeIs('admin.categories')" icon="tag">Categories</x-admin.nav-link>
                    <x-admin.nav-link :href="route('admin.cakes.index')" :active="request()->routeIs('admin.cakes.*')" icon="cake">Cakes</x-admin.nav-link>
                    <x-admin.nav-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')" icon="package">Orders</x-admin.nav-link>
                    <x-admin.nav-link :href="route('admin.designer')" :active="request()->routeIs('admin.designer')" icon="wand">Designer</x-admin.nav-link>
                    <x-admin.nav-link :href="route('admin.testimonials')" :active="request()->routeIs('admin.testimonials')" icon="message">Testimonials</x-admin.nav-link>
                    <x-admin.nav-link :href="route('admin.customers')" :active="request()->routeIs('admin.customers')" icon="users">Customers</x-admin.nav-link>
                </nav>
                <div class="border-t border-border p-3">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold text-muted-foreground hover:bg-muted">
                        <x-icon name="home" class="size-4" /> View store
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 rounded-md px-3 py-2 text-sm font-semibold text-muted-foreground hover:bg-muted">
                            <x-icon name="log-out" class="size-4" /> Sign out
                        </button>
                    </form>
                </div>
            </aside>
            <div class="flex min-w-0 flex-1 flex-col">
                <header class="flex items-center justify-between border-b border-border bg-card px-4 py-3 lg:px-8">
                    <p class="font-display text-lg font-bold lg:hidden">Bakeq Admin</p>
                    <p class="hidden text-sm text-muted-foreground lg:block">{{ auth()->user()->name }}</p>
                    <a href="{{ route('home') }}" class="rounded-md bg-secondary px-4 py-2 text-xs font-bold uppercase tracking-wider text-secondary-foreground">Storefront</a>
                </header>
                <main class="flex-1 p-4 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
