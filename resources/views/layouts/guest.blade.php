<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'Sign in' }} · {{ $brandShortName }}</title>
        <x-favicons />
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-background text-foreground antialiased">
        <div class="flex min-h-screen items-center justify-center p-4 sm:p-8">
            <div class="w-full max-w-md animate-hero-enter">
                <a href="{{ route('home') }}" class="mb-8 flex flex-col items-center">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ $brandName }} logo" class="h-32 w-auto" decoding="async">
                </a>
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
