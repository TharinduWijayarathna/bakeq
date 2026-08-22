@props([
    'label',
    'value',
    'hint' => null,
    'icon',
])

<x-admin.panel class="p-5">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ $label }}</p>
            <p class="mt-2 truncate font-display text-3xl font-bold leading-none">{{ $value }}</p>
            @if ($hint)
                <p class="mt-2 text-xs text-muted-foreground">{{ $hint }}</p>
            @endif
        </div>
        <span class="grid size-9 shrink-0 place-items-center rounded-md bg-secondary text-primary">
            <x-icon :name="$icon" class="size-4" />
        </span>
    </div>
</x-admin.panel>
