<div>
    <p class="font-script text-3xl text-primary">Kitchen</p>
    <h1 class="mt-1 text-4xl">Orders</h1>
    <x-flash />

    <div class="mt-6 flex flex-wrap gap-2">
        <button type="button" wire:click="setTab('online')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $tab === 'online' ? 'bg-primary text-primary-foreground' : 'bg-card' }}">Online</button>
        <button type="button" wire:click="setTab('manual')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $tab === 'manual' ? 'bg-primary text-primary-foreground' : 'bg-card' }}">Manual</button>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" wire:click="$set('status', '')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $status === '' ? 'bg-secondary text-secondary-foreground' : 'bg-card' }}">All statuses</button>
        @foreach ($statuses as $item)
            <button type="button" wire:key="st-{{ $item->value }}" wire:click="$set('status', '{{ $item->value }}')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $status === $item->value ? 'bg-secondary text-secondary-foreground' : 'bg-card' }}">
                {{ $item->label() }}
            </button>
        @endforeach
    </div>

    @if ($tab === 'manual')
        <form wire:submit="createWalkIn" class="mt-8 grid gap-4 rounded-4xl bg-card p-6 shadow-soft lg:grid-cols-2">
            <h2 class="text-xl font-bold lg:col-span-2">Create walk-in order</h2>
            <div>
                <label class="mb-1 block text-sm font-semibold">Customer</label>
                <select wire:model="user_id" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                    <option value="">Choose customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                    @endforeach
                </select>
                @error('user_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Cake</label>
                <select wire:model="cake_id" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                    <option value="">Choose cake</option>
                    @foreach ($cakes as $cake)
                        <option value="{{ $cake->id }}">{{ $cake->name }} — {{ $cake->formattedPrice() }}</option>
                    @endforeach
                </select>
                @error('cake_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Quantity</label>
                <input type="number" wire:model="quantity" min="1" max="50" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('quantity') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Fulfillment</label>
                <select wire:model="fulfillment_method" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                    @foreach ($fulfillmentMethods as $method)
                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Date</label>
                <input type="date" wire:model="delivery_date" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('delivery_date') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Address / pickup note</label>
                <input type="text" wire:model="delivery_address" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('delivery_address') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1 block text-sm font-semibold">Notes</label>
                <textarea wire:model="notes" rows="2" class="w-full rounded-2xl border border-input px-4 py-3 text-sm"></textarea>
            </div>
            <div class="lg:col-span-2">
                <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Create walk-in order</button>
            </div>
        </form>
    @endif

    <div class="mt-6 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Origin</th>
                    <th class="px-4 py-3">Total due</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr wire:key="ao-{{ $order->id }}" class="border-t border-border">
                        <td class="px-4 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-primary">#{{ $order->id }}</a></td>
                        <td class="px-4 py-3">{{ $order->user->name }}</td>
                        <td class="px-4 py-3">{{ $order->delivery_date->toFormattedDateString() }}</td>
                        <td class="px-4 py-3">{{ $order->status->label() }}</td>
                        <td class="px-4 py-3">
                            @if ($order->origin->isAiDesigned())
                                <span class="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-bold text-primary">AI Designed</span>
                            @else
                                <span class="text-muted-foreground">Catalog</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $order->formattedTotalDue() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-muted-foreground">No {{ $tab }} orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
