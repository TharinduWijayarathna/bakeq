<div>
    <a href="{{ route('admin.customers') }}" class="text-sm font-semibold text-muted-foreground" wire:navigate>← Customers</a>
    <p class="mt-4 font-script text-3xl text-primary">CRM</p>
    <h1 class="mt-1 text-4xl">{{ $customer->name }}</h1>
    <p class="mt-2 text-sm text-muted-foreground">{{ $customer->email }} · {{ $customer->phone ?: 'No phone' }} · {{ $customer->city ?: 'No city' }}</p>
    <x-flash />

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-4xl bg-card p-5 shadow-soft">
            <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Lifetime spend</p>
            <p class="mt-2 text-2xl font-bold">{{ $lifetimeSpend }}</p>
        </div>
        <div class="rounded-4xl bg-card p-5 shadow-soft sm:col-span-2">
            <p class="text-xs font-bold uppercase tracking-wider text-muted-foreground">Favorite flavours / cakes</p>
            <ul class="mt-3 flex flex-wrap gap-2">
                @forelse ($favoriteFlavors as $name => $qty)
                    <li class="rounded-full bg-secondary px-3 py-1 text-xs font-semibold">{{ $name }} · {{ $qty }}</li>
                @empty
                    <li class="text-sm text-muted-foreground">No purchases yet.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <form wire:submit="saveNotes" class="mt-6 rounded-4xl bg-card p-6 shadow-soft">
        <label class="mb-1 block text-sm font-semibold">Loyalty notes</label>
        <textarea wire:model="loyalty_notes" rows="4" class="w-full rounded-3xl border border-input px-4 py-3 text-sm" placeholder="VIP regular, prefers less sugar, allergic to walnuts…"></textarea>
        <button type="submit" class="mt-4 rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Save notes</button>
    </form>

    <div class="mt-8 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    <tr wire:key="crm-order-{{ $order->id }}" class="border-t border-border">
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-primary" wire:navigate>#{{ $order->id }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $order->status->label() }}</td>
                        <td class="px-4 py-3">{{ $order->created_at?->toFormattedDateString() }}</td>
                        <td class="px-4 py-3">{{ $order->formattedSubtotal() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-muted-foreground">No orders yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
