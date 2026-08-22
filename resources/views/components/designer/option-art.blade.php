@props([
    'option',
    'selected' => false,
])

@php
    $url = $option->illustrationUrl();
    $color = $option->color_hex ?? '#f4a6bf';
@endphp

<span
    {{ $attributes->class('relative block overflow-hidden rounded-2xl') }}
    style="background: color-mix(in oklab, {{ $color }} 32%, white)"
>
    @if ($url)
        <img src="{{ $url }}" alt="" loading="lazy" decoding="async" class="mx-auto h-28 w-full object-contain p-2 sm:h-32">
    @else
        <span class="block h-20" style="background: {{ $color }}"></span>
    @endif
    @if ($selected)
        <span class="absolute right-2 top-2 grid size-6 place-items-center rounded-full bg-primary text-primary-foreground shadow-soft">
            <x-icon name="check" class="size-3.5" />
        </span>
    @endif
</span>
