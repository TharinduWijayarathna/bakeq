@props([
    'active' => false,
    'pollMethod' => 'refreshPreview',
    'target' => 'generate',
])

<div
    @if ($active)
        wire:poll.2s="{{ $pollMethod }}"
    @else
        wire:loading.flex
        wire:target="{{ $target }}"
    @endif
    {{ $attributes->class([
        'absolute inset-0 z-10 items-center justify-center overflow-hidden bg-card/92 px-6 text-center backdrop-blur-md',
        'flex' => $active,
    ]) }}
    role="status"
    aria-live="polite"
    aria-label="Baking your cake preview"
>
    <div class="relative w-full max-w-[16rem]">
        <span class="absolute left-6 top-4 size-2.5 animate-sparkle-twinkle rounded-full bg-primary delay-100"></span>
        <span class="absolute right-8 top-2 size-2 animate-sparkle-twinkle rounded-full bg-accent delay-300"></span>
        <span class="absolute right-10 top-16 size-1.5 animate-sparkle-twinkle rounded-full bg-primary delay-700"></span>
        <span class="absolute left-10 top-14 size-2 animate-sparkle-twinkle rounded-full bg-[#c9a227] delay-150"></span>

        <div class="relative mx-auto h-28 w-28">
            <span class="absolute left-1/2 top-1 h-6 w-1.5 -translate-x-4 animate-steam-rise rounded-full bg-primary/40"></span>
            <span class="absolute left-1/2 top-0 h-7 w-1.5 -translate-x-1/2 animate-steam-rise rounded-full bg-primary/50 delay-300"></span>
            <span class="absolute left-1/2 top-1 h-6 w-1.5 translate-x-2.5 animate-steam-rise rounded-full bg-accent/50 delay-700"></span>

            <svg class="animate-cake-float mx-auto mt-6 h-20 w-20" viewBox="0 0 80 80" fill="none" aria-hidden="true">
                <ellipse cx="40" cy="68" rx="24" ry="5" class="fill-primary/20"/>
                <path d="M16 54c0-4 7-8 24-8s24 4 24 8c0 8-11 13-24 13S16 62 16 54Z" fill="#f4a6bf"/>
                <path d="M22 48c2-12 8-22 18-22s16 10 18 22" fill="#ffd6e5"/>
                <path d="M28 46c2-8 6-14 12-14s10 6 12 14" fill="#fff"/>
                <path d="M16 54c3 2 12 4 24 4s21-2 24-4" stroke="#e11d74" stroke-width="1.4" stroke-linecap="round" opacity=".4"/>
                <rect x="30" y="22" width="3" height="12" rx="1.5" fill="#7dd3c0"/>
                <rect x="38.5" y="18" width="3" height="16" rx="1.5" fill="#e11d74"/>
                <rect x="47" y="22" width="3" height="12" rx="1.5" fill="#c9a227"/>
                <ellipse cx="31.5" cy="20" rx="2.2" ry="3" fill="#ffb703"/>
                <ellipse cx="40" cy="16" rx="2.2" ry="3" fill="#ffb703"/>
                <ellipse cx="48.5" cy="20" rx="2.2" ry="3" fill="#ffb703"/>
            </svg>
        </div>

        <p class="mt-2 font-display text-lg font-bold">Baking your cake</p>
        <p class="relative mx-auto mt-1 h-6 overflow-hidden text-sm leading-6 text-muted-foreground">
            <span class="absolute inset-x-0 top-0 animate-bake-status">Mixing the batter…</span>
            <span class="absolute inset-x-0 top-0 animate-bake-status opacity-0" style="animation-delay: 2.2s">Layering the sponge…</span>
            <span class="absolute inset-x-0 top-0 animate-bake-status opacity-0" style="animation-delay: 4.4s">Piping the frosting…</span>
            <span class="absolute inset-x-0 top-0 animate-bake-status opacity-0" style="animation-delay: 6.6s">Adding the sparkle…</span>
        </p>
        <p class="mt-2 text-[11px] text-muted-foreground">This can take a little while.</p>

        <div class="relative mt-4 h-1.5 overflow-hidden rounded-full bg-secondary">
            <span class="absolute inset-y-0 w-1/3 animate-oven-shimmer rounded-full bg-gradient-sweet"></span>
        </div>
    </div>
</div>
