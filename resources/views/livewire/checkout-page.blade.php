<div class="mx-auto max-w-3xl px-5 py-12">
    <p class="font-script text-3xl text-primary animate-hero-enter">Checkout</p>
    <h1 class="mt-1 text-4xl animate-hero-enter">Confirm your order</h1>
    <x-flash />

    @if ($items->isEmpty())
        <p class="mt-8 text-muted-foreground">Add cakes to your cart first.</p>
    @else
        <ul class="mt-8 space-y-2 text-sm">
            @foreach ($items as $item)
                <li wire:key="check-{{ $item->id }}" class="flex justify-between rounded-2xl bg-card px-4 py-3 shadow-soft">
                    <span>{{ $item->displayName() }} × {{ $item->quantity }}</span>
                    <span class="font-bold">{{ \App\Support\Money::format($item->lineTotal()) }}</span>
                </li>
            @endforeach
        </ul>
        <p class="mt-4 text-right text-lg font-bold">Total {{ $total }}</p>
        <p class="mt-2 text-sm text-muted-foreground">{{ $settings->notice }} Minimum lead time: {{ $settings->lead_days }} days.</p>

        <form wire:submit="placeOrder" class="mt-8 space-y-4 rounded-4xl bg-card p-6 shadow-soft" x-reveal>
            <div>
                <label class="mb-1 block text-sm font-semibold">Delivery date</label>
                <input type="date" wire:model="delivery_date" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                @error('delivery_date') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Delivery address</label>
                <input type="text" wire:model="delivery_address" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                @error('delivery_address') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Notes</label>
                <textarea wire:model="notes" rows="3" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm"></textarea>
            </div>
            <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-full bg-primary px-8 py-3 text-sm font-bold text-primary-foreground disabled:opacity-70">
                <span wire:loading.remove>Place order</span>
                <span wire:loading.flex class="items-center gap-2">
                    <x-spinner /> Placing order…
                </span>
            </button>
        </form>
    @endif
</div>
