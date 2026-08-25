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

        <div class="mt-4 space-y-2 rounded-4xl bg-secondary p-5 text-sm">
            <div class="flex justify-between gap-4"><span>Base / items</span><span class="font-semibold">{{ $formatted['subtotal'] }}</span></div>
            <div class="flex justify-between gap-4"><span>Add-ons</span><span class="font-semibold">{{ $formatted['addons_total'] }}</span></div>
            <div class="flex justify-between gap-4"><span>{{ $fulfillment_method === 'pickup' ? 'Pickup fee' : 'Delivery fee' }}</span><span class="font-semibold">{{ $formatted['delivery_fee'] }}</span></div>
            <div class="flex justify-between gap-4"><span>Tax</span><span class="font-semibold">{{ $formatted['tax_amount'] }}</span></div>
            <div class="flex justify-between gap-4"><span>Deposit paid</span><span class="font-semibold">− {{ $formatted['deposit_paid'] }}</span></div>
            <div class="flex justify-between gap-4 border-t border-border/60 pt-2 text-base">
                <span class="font-bold">Total due</span>
                <span class="font-bold text-primary">{{ $formatted['total_due'] }}</span>
            </div>
        </div>

        <p class="mt-2 text-sm text-muted-foreground">{{ $settings->notice }} Minimum lead time: {{ $settings->lead_days }} days.</p>

        <form wire:submit="placeOrder" class="mt-8 space-y-4 rounded-4xl bg-card p-6 shadow-soft" x-reveal>
            <div>
                <label class="mb-2 block text-sm font-semibold">Fulfillment</label>
                <div class="flex flex-wrap gap-2">
                    @foreach ($fulfillmentMethods as $method)
                        <label wire:key="check-fulfill-{{ $method->value }}" class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-border px-4 py-2 text-sm font-semibold {{ $fulfillment_method === $method->value ? 'border-primary bg-primary/10 text-primary' : '' }}">
                            <input type="radio" wire:model.live="fulfillment_method" value="{{ $method->value }}" class="sr-only">
                            {{ $method->label() }}
                        </label>
                    @endforeach
                </div>
                @error('fulfillment_method') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Delivery / pickup date</label>
                <input type="date" wire:model="delivery_date" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                @error('delivery_date') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">{{ $fulfillment_method === 'pickup' ? 'Pickup notes / address' : 'Delivery address' }}</label>
                <input type="text" wire:model="delivery_address" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                @error('delivery_address') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Order notes</label>
                <textarea wire:model="notes" rows="3" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm" placeholder="Allergies, message on cake, timing…"></textarea>
                @error('notes') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
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
