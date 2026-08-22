@props([
    'count' => 6,
])

<div {{ $attributes->class('grid gap-6 sm:grid-cols-2 lg:grid-cols-3') }} role="status" aria-live="polite" aria-label="Loading cakes">
    @foreach (range(1, $count) as $index)
        <div wire:key="cake-skeleton-{{ $index }}" class="overflow-hidden rounded-4xl bg-card shadow-soft">
            <div class="skeleton h-64"></div>
            <div class="space-y-3 p-6">
                <div class="skeleton h-6 w-2/3 rounded-full"></div>
                <div class="skeleton h-4 w-full rounded-full"></div>
                <div class="skeleton h-4 w-1/2 rounded-full"></div>
                <div class="flex items-center justify-between pt-2">
                    <div class="skeleton h-6 w-20 rounded-full"></div>
                    <div class="skeleton h-9 w-16 rounded-full"></div>
                </div>
            </div>
        </div>
    @endforeach
    <span class="sr-only">Loading cakes</span>
</div>
