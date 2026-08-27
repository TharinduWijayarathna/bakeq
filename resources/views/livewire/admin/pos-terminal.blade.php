<div>
    <p class="font-script text-3xl text-primary">Counter</p>
    <h1 class="mt-1 text-4xl">POS</h1>
    <x-flash />

    <form wire:submit="checkout" class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div class="space-y-4 rounded-4xl bg-card p-6 shadow-soft">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-semibold">Customer</label>
                    <select wire:model="user_id" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                        <option value="">Choose</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                    @error('user_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Payment</label>
                    <select wire:model="payment_method" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                        @foreach ($paymentMethods as $method)
                            <option value="{{ $method->value }}">{{ $method->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">Line items</h2>
                <div class="flex gap-2">
                    <button type="button" wire:click="addCakeLine" class="rounded-full bg-muted px-3 py-1.5 text-xs font-bold uppercase">Add line</button>
                </div>
            </div>
            @error('lines') <p class="text-sm text-destructive">{{ $message }}</p> @enderror

            @foreach ($lines as $index => $line)
                <div wire:key="pos-line-{{ $index }}" class="grid gap-3 rounded-3xl bg-muted/40 p-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-xs font-bold uppercase">Cake (optional)</label>
                        <select wire:model.live="lines.{{ $index }}.cake_id" class="w-full rounded-2xl border border-input bg-background px-4 py-2.5 text-sm">
                            <option value="">Ad-hoc item</option>
                            @foreach ($cakes as $cake)
                                <option value="{{ $cake->id }}">{{ $cake->name }} - {{ $cake->formattedPrice() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase">Name</label>
                        <input type="text" wire:model="lines.{{ $index }}.name" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Qty</label>
                            <input type="number" min="1" wire:model="lines.{{ $index }}.quantity" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Price Rs.</label>
                            <input type="number" min="0" step="1" wire:model="lines.{{ $index }}.unit_price_rupees" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
                        </div>
                    </div>
                    <div class="sm:col-span-2">
                        <button type="button" wire:click="removeLine({{ $index }})" class="text-xs font-bold uppercase text-destructive">Remove</button>
                    </div>
                </div>
            @endforeach

            <div class="grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="mb-1 block text-sm font-semibold">Discount type</label>
                    <select wire:model="discount_type" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                        @foreach ($discountTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold">Discount value</label>
                    <input type="number" min="0" step="0.01" wire:model="discount_value" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                </div>
                <div class="sm:col-span-3">
                    <label class="mb-1 block text-sm font-semibold">Notes</label>
                    <textarea wire:model="notes" rows="2" class="w-full rounded-2xl border border-input px-4 py-3 text-sm"></textarea>
                </div>
            </div>

            <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Complete sale</button>
        </div>

        <div class="rounded-4xl bg-secondary p-6 shadow-soft">
            <h2 class="text-xl font-bold">Last receipt</h2>
            @if ($lastOrder)
                <p class="mt-2 text-sm text-muted-foreground">{{ $lastOrder->receipt_number }}</p>
                <p class="mt-1 font-semibold">{{ $lastOrder->user->name }}</p>
                <ul class="mt-4 space-y-2 text-sm">
                    @foreach ($lastOrder->items as $item)
                        <li wire:key="rcpt-{{ $item->id }}" class="flex justify-between gap-3">
                            <span>{{ $item->name }} × {{ $item->quantity }}</span>
                            <span>{{ \App\Support\Money::format($item->unit_price * $item->quantity) }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($lastOrder->discount_amount > 0)
                    <p class="mt-3 flex justify-between text-sm"><span>Discount</span><span>− {{ \App\Support\Money::format($lastOrder->discount_amount) }}</span></p>
                @endif
                <p class="mt-3 flex justify-between border-t border-border/50 pt-3 text-base font-bold">
                    <span>Total due</span>
                    <span>{{ $lastOrder->formattedTotalDue() }}</span>
                </p>
                <p class="mt-2 text-sm">Paid by {{ $lastOrder->payment_method?->label() }}</p>
                @if ($lastOrder->invoice)
                    <a href="{{ route('admin.invoices.download', $lastOrder->invoice) }}" class="mt-4 inline-flex rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground">Download invoice PDF</a>
                @endif
            @else
                <p class="mt-4 text-sm text-muted-foreground">Complete a sale to see the receipt here.</p>
            @endif
        </div>
    </form>
</div>
