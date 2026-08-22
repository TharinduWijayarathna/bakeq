<div>
    <p class="font-script text-3xl text-primary">People</p>
    <h1 class="mt-1 text-4xl">Customers</h1>

    <div class="mt-8 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">City</th>
                    <th class="px-4 py-3">Orders</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr wire:key="cu-{{ $customer->id }}" class="border-t border-border">
                        <td class="px-4 py-3 font-semibold">{{ $customer->name }}</td>
                        <td class="px-4 py-3">{{ $customer->email }}</td>
                        <td class="px-4 py-3">{{ $customer->city ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $customer->orders_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-muted-foreground">No customers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
