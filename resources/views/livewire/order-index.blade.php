<div class="mx-auto max-w-4xl px-5 py-12">
    <p class="font-script text-3xl text-primary">Orders</p>
    <h1 class="mt-1 text-4xl">Your cake orders</h1>
    <x-flash />

    @forelse ($orders as $order)
        <article wire:key="order-{{ $order->id }}" class="mt-6 rounded-4xl bg-card p-6 shadow-soft">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-xl">Order #{{ $order->id }}</h2>
                <span class="rounded-full bg-secondary px-3 py-1 text-xs font-bold uppercase">{{ $order->status->label() }}</span>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">Delivery {{ $order->delivery_date->toFormattedDateString() }} · {{ $order->formattedSubtotal() }}</p>
            <ul class="mt-4 space-y-1 text-sm">
                @foreach ($order->items as $item)
                    <li wire:key="oi-{{ $item->id }}">{{ $item->name }} × {{ $item->quantity }}</li>
                @endforeach
            </ul>
        </article>
    @empty
        <p class="mt-8 text-muted-foreground">You have not placed an order yet.</p>
    @endforelse
</div>
