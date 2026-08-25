<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name') }} · {{ $brandName }}</title>
        <x-favicons />
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background text-foreground antialiased">
        <livewire:storefront.header />

        <main>
            {{ $slot }}
        </main>

        <x-storefront.footer />

        <x-storefront.whatsapp-button />

        @unless (request()->routeIs('assistant'))
            <a
                href="{{ route('assistant') }}"
                class="fixed {{ request()->routeIs('designer') ? 'bottom-24 lg:bottom-5' : 'bottom-5' }} right-5 z-40 inline-flex items-center gap-2 rounded-full bg-primary px-4 py-3 text-sm font-bold text-primary-foreground shadow-sweet transition hover:-translate-y-0.5 animate-hero-enter [animation-delay:650ms]"
            >
                <x-icon name="sparkle" class="size-4" />
                Ask a question
            </a>
        @endunless
    </body>
</html>
