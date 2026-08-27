<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Admin' }} · {{ $brandShortName }}</title>
        <x-favicons />
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="admin-app min-h-screen bg-background text-foreground antialiased">
        <div class="flex min-h-screen">
            <aside class="sticky top-0 hidden h-svh w-64 shrink-0 border-r border-border bg-card lg:flex lg:flex-col">
                <div class="h-1.5 shrink-0 bg-gradient-sweet" aria-hidden="true"></div>
                <a href="{{ route('admin.dashboard') }}" class="flex shrink-0 items-center gap-3 px-5 py-5">
                    <img src="{{ asset('images/logo-mark.png') }}" alt="" class="size-10 object-contain">
                    <div>
                        <p class="font-display text-lg font-bold leading-tight text-primary">{{ $brandShortName }}</p>
                        <p class="text-xs text-muted-foreground">{{ auth()->user()->role->label() }}</p>
                    </div>
                </a>
                <nav class="flex min-h-0 flex-1 flex-col gap-1 overflow-y-auto px-3 py-2 text-sm font-semibold">
                    @if (auth()->user()->canAccess('dashboard'))
                        <x-admin.nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')" icon="layout">Dashboard</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('categories'))
                        <x-admin.nav-link :href="route('admin.categories')" :active="request()->routeIs('admin.categories')" icon="tag">Categories</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('cakes'))
                        <x-admin.nav-link :href="route('admin.cakes.index')" :active="request()->routeIs('admin.cakes.*')" icon="cake">Cakes</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('inventory'))
                        <x-admin.nav-link :href="route('admin.inventory')" :active="request()->routeIs('admin.inventory')" icon="layers">Inventory</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('recipes'))
                        <x-admin.nav-link :href="route('admin.recipes.index')" :active="request()->routeIs('admin.recipes.*')" icon="clipboard">Recipes</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('orders'))
                        <x-admin.nav-link :href="route('admin.orders.index')" :active="request()->routeIs('admin.orders.*')" icon="package">Orders</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('pos'))
                        <x-admin.nav-link :href="route('admin.pos')" :active="request()->routeIs('admin.pos')" icon="banknote">POS</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('admin-agent'))
                        <x-admin.nav-link :href="route('admin.admin-agent')" :active="request()->routeIs('admin.admin-agent')" icon="sparkle">Admin Agent</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('production'))
                        <x-admin.nav-link :href="route('admin.production')" :active="request()->routeIs('admin.production')" icon="layers">Production</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('waste'))
                        <x-admin.nav-link :href="route('admin.waste')" :active="request()->routeIs('admin.waste')" icon="trash">Waste</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('invoices'))
                        <x-admin.nav-link :href="route('admin.invoices.index')" :active="request()->routeIs('admin.invoices.*')" icon="clipboard">Invoices</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('designer'))
                        <x-admin.nav-link :href="route('admin.designer')" :active="request()->routeIs('admin.designer')" icon="wand">Designer</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('gallery'))
                        <x-admin.nav-link :href="route('admin.gallery')" :active="request()->routeIs('admin.gallery')" icon="image">Gallery</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('testimonials'))
                        <x-admin.nav-link :href="route('admin.testimonials')" :active="request()->routeIs('admin.testimonials')" icon="message">Testimonials</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('customers'))
                        <x-admin.nav-link :href="route('admin.customers')" :active="request()->routeIs('admin.customers*')" icon="users">Customers</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('employees'))
                        <x-admin.nav-link :href="route('admin.employees')" :active="request()->routeIs('admin.employees')" icon="users">Employees</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('shifts'))
                        <x-admin.nav-link :href="route('admin.shifts')" :active="request()->routeIs('admin.shifts')" icon="settings">Shifts</x-admin.nav-link>
                    @endif
                    @if (auth()->user()->canAccess('audit'))
                        <x-admin.nav-link :href="route('admin.audit')" :active="request()->routeIs('admin.audit')" icon="clipboard">Audit</x-admin.nav-link>
                    @endif
                </nav>
                <div class="shrink-0 border-t border-border p-3">
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
                    <p class="font-display text-lg font-bold lg:hidden">{{ $brandAdminLabel }}</p>
                    <p class="hidden text-sm text-muted-foreground lg:block">{{ auth()->user()->name }} · {{ auth()->user()->role->label() }}</p>
                    <a href="{{ route('home') }}" class="rounded-md bg-secondary px-4 py-2 text-xs font-bold uppercase tracking-wider text-secondary-foreground">Storefront</a>
                </header>
                <main class="flex-1 p-4 lg:p-8">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
