<div>
    <p class="font-script text-3xl text-primary">Kitchen</p>
    <h1 class="mt-1 text-4xl">Orders</h1>

    <div class="mt-6 flex flex-wrap gap-2">
        <button type="button" wire:click="$set('status', '')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $status === '' ? 'bg-primary text-primary-foreground' : 'bg-card' }}">All</button>
        @foreach ($statuses as $item)
            <button type="button" wire:key="st-{{ $item->value }}" wire:click="$set('status', '{{ $item->value }}')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $status === $item->value ? 'bg-primary text-primary-foreground' : 'bg-card' }}">
                {{ $item->label() }}
            </button>
        @endforeach
    </div>

    <div class="mt-6 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr wire:key="ao-{{ $order->id }}" class="border-t border-border">
                        <td class="px-4 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-primary">#{{ $order->id }}</a></td>
                        <td class="px-4 py-3">{{ $order->user->name }}</td>
                        <td class="px-4 py-3">{{ $order->delivery_date->toFormattedDateString() }}</td>
                        <td class="px-4 py-3">{{ $order->status->label() }}</td>
                        <td class="px-4 py-3">{{ $order->formattedSubtotal() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-muted-foreground">No orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
