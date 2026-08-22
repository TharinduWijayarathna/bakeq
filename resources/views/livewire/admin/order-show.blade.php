<div>
    <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-muted-foreground">All orders</a>
    <h1 class="mt-2 text-4xl">Order #{{ $order->id }}</h1>
    <x-flash />

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-xl">Customer</h2>
            <p class="mt-2 text-sm">{{ $order->user->name }}</p>
            <p class="text-sm text-muted-foreground">{{ $order->user->email }}</p>
            <p class="mt-3 text-sm">{{ $order->delivery_address }}</p>
            <p class="text-sm text-muted-foreground">Delivery {{ $order->delivery_date->toFormattedDateString() }}</p>
            @if ($order->notes)
                <p class="mt-3 text-sm">Notes: {{ $order->notes }}</p>
            @endif
        </div>
        <div class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-xl">Status</h2>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($statuses as $status)
                    <button type="button" wire:click="updateStatus('{{ $status->value }}')" class="rounded-full px-4 py-2 text-xs font-bold uppercase {{ $order->status === $status ? 'bg-primary text-primary-foreground' : 'bg-muted' }}">
                        {{ $status->label() }}
                    </button>
                @endforeach
            </div>
            <p class="mt-4 text-lg font-bold">{{ $order->formattedSubtotal() }}</p>
        </div>
    </div>

    <ul class="mt-6 space-y-2 rounded-4xl bg-card p-6 shadow-soft">
        @foreach ($order->items as $item)
            <li wire:key="item-{{ $item->id }}" class="flex justify-between text-sm">
                <span>{{ $item->name }} × {{ $item->quantity }}</span>
                <span class="font-semibold">{{ $item->formattedUnitPrice() }}</span>
            </li>
        @endforeach
    </ul>
</div>
