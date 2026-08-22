@props([
    'src',
    'alt' => '',
    'eager' => false,
])

<img
    src="{{ $src }}"
    alt="{{ $alt }}"
    loading="{{ $eager ? 'eager' : 'lazy' }}"
    decoding="async"
    @if ($eager)
        fetchpriority="high"
    @else
        x-data="lazyImage"
        x-on:load="loaded = true"
        x-bind:class="loaded ? 'opacity-100' : 'opacity-0'"
    @endif
    {{ $attributes->class(['bg-muted transition-opacity duration-700' => ! $eager]) }}
>
