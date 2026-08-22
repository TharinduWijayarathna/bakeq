@props([
    'message' => null,
])

@php
    $flash = $message ?? session('status') ?? session('success');
    $error = session('error');
@endphp

@if ($flash)
    <div class="mb-6 animate-fade-in rounded-3xl bg-secondary px-4 py-3 text-sm font-semibold text-secondary-foreground" role="status">
        {{ $flash }}
    </div>
@endif

@if ($error)
    <div class="mb-6 animate-fade-in rounded-3xl bg-destructive/10 px-4 py-3 text-sm font-semibold text-destructive" role="alert">
        {{ $error }}
    </div>
@endif
