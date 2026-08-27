@props([
    'target' => 'ask,askSuggestion',
    'title' => 'Finding a short answer',
])

<div
    wire:loading.flex
    wire:target="{{ $target }}"
    {{ $attributes->class('absolute inset-0 z-10 items-center justify-center overflow-hidden bg-card/92 px-6 text-center backdrop-blur-md') }}
    role="status"
    aria-live="polite"
    aria-label="{{ $title }}"
>
    <div class="relative w-full max-w-[16rem]">
        <span class="absolute left-6 top-4 size-2.5 animate-sparkle-twinkle rounded-full bg-primary delay-100"></span>
        <span class="absolute right-8 top-2 size-2 animate-sparkle-twinkle rounded-full bg-accent delay-300"></span>
        <span class="absolute right-10 top-16 size-1.5 animate-sparkle-twinkle rounded-full bg-primary delay-700"></span>

        <div class="mx-auto grid size-16 place-items-center rounded-3xl bg-secondary text-primary shadow-soft">
            <x-icon name="sparkle" class="size-7 animate-cake-float" />
        </div>

        <p class="mt-4 font-display text-lg font-bold">{{ $title }}</p>
        <p class="relative mx-auto mt-1 h-6 overflow-hidden text-sm leading-6 text-muted-foreground">
            <span class="absolute inset-x-0 top-0 animate-bake-status">Reading your question…</span>
            <span class="absolute inset-x-0 top-0 animate-bake-status opacity-0" style="animation-delay: 2.2s">Checking cake basics…</span>
            <span class="absolute inset-x-0 top-0 animate-bake-status opacity-0" style="animation-delay: 4.4s">Keeping it simple…</span>
            <span class="absolute inset-x-0 top-0 animate-bake-status opacity-0" style="animation-delay: 6.6s">Almost ready…</span>
        </p>
        <p class="mt-2 text-[11px] text-muted-foreground">This only takes a moment.</p>

        <div class="relative mt-4 h-1.5 overflow-hidden rounded-full bg-secondary">
            <span class="absolute inset-y-0 w-1/3 animate-oven-shimmer rounded-full bg-gradient-sweet"></span>
        </div>
    </div>
</div>
