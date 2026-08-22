@props([
    'compact' => false,
    'generating' => false,
])

<button
    type="button"
    wire:click="generate"
    wire:loading.attr="disabled"
    wire:target="generate"
    @disabled($generating)
    {{ $attributes->class([
        'inline-flex items-center justify-center gap-2 rounded-full bg-primary font-bold text-primary-foreground disabled:opacity-70',
        'w-full py-3.5 text-sm' => ! $compact,
        'px-5 py-3 text-xs' => $compact,
    ]) }}
>
    @if ($generating)
        <span class="inline-flex items-center gap-2">
            <x-spinner />
            Baking…
        </span>
    @else
        <span wire:loading.remove wire:target="generate" class="inline-flex items-center gap-2">
            <x-icon name="sparkle" class="size-4" />
            Generate cake
        </span>
        <span wire:loading.flex wire:target="generate" class="items-center gap-2">
            <x-spinner />
            Baking…
        </span>
    @endif
</button>
