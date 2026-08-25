<div>
    <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-muted-foreground">All orders</a>
    <h1 class="mt-2 text-4xl">Order #{{ $order->id }}</h1>
    <x-flash />

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-xl">Customer</h2>
            <p class="mt-2 text-sm">
                <a href="{{ route('admin.customers.show', $order->user) }}" class="font-semibold text-primary" wire:navigate>{{ $order->user->name }}</a>
            </p>
            <p class="text-sm text-muted-foreground">{{ $order->user->email }}</p>
            <p class="mt-3 text-sm">{{ $order->delivery_address }}</p>
            <p class="text-sm text-muted-foreground">{{ $order->fulfillment_method->label() }} · {{ $order->delivery_date->toFormattedDateString() }}</p>
            <p class="mt-2 text-xs text-muted-foreground">Source: {{ $order->order_source->label() }}</p>
            @if ($order->origin->isAiDesigned())
                <span class="mt-3 inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-bold text-primary">AI Designed</span>
            @endif
            @if ($order->invoice)
                <p class="mt-3 text-sm">
                    <a href="{{ route('admin.invoices.download', $order->invoice) }}" class="font-semibold text-primary">Download invoice {{ $order->invoice->number }}</a>
                </p>
            @endif
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
            @error('status')
                <p class="mt-3 rounded-2xl bg-destructive/10 px-3 py-2 text-sm text-destructive">{{ $message }}</p>
            @enderror
            <dl class="mt-4 space-y-1 text-sm">
                <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Items</dt><dd>{{ $order->formattedSubtotal() }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Add-ons</dt><dd>{{ \App\Support\Money::format($order->addons_total) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Delivery / pickup</dt><dd>{{ \App\Support\Money::format($order->delivery_fee) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Tax</dt><dd>{{ \App\Support\Money::format($order->tax_amount) }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-muted-foreground">Deposit</dt><dd>− {{ \App\Support\Money::format($order->deposit_paid) }}</dd></div>
                <div class="flex justify-between gap-3 border-t border-border pt-2 text-base font-bold"><dt>Total due</dt><dd class="text-primary">{{ $order->formattedTotalDue() }}</dd></div>
            </dl>
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
