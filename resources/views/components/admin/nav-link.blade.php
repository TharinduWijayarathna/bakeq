@props([
    'href',
    'active' => false,
    'icon',
])

<a
    href="{{ $href }}"
    {{ $attributes->merge(['class' => $active
        ? 'flex items-center gap-2 rounded-md bg-primary px-3 py-2.5 text-primary-foreground'
        : 'flex items-center gap-2 rounded-md px-3 py-2.5 text-foreground hover:bg-muted']) }}
>
    <x-icon :name="$icon" class="size-4" />
    {{ $slot }}
</a>
