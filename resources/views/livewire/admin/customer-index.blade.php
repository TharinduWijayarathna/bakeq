<div>
    <p class="font-script text-3xl text-primary">People</p>
    <h1 class="mt-1 text-4xl">Customers</h1>
    <x-flash />

    <div class="mt-6 flex flex-wrap gap-2">
        <button type="button" wire:click="setTab('online')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $tab === 'online' ? 'bg-primary text-primary-foreground' : 'bg-card' }}">Online</button>
        <button type="button" wire:click="setTab('manual')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $tab === 'manual' ? 'bg-primary text-primary-foreground' : 'bg-card' }}">Manual</button>
    </div>

    @if ($tab === 'manual')
        <form wire:submit="createManual" class="mt-8 grid gap-4 rounded-4xl bg-card p-6 shadow-soft sm:grid-cols-2">
            <h2 class="text-xl font-bold sm:col-span-2">Create manual customer</h2>
            <div>
                <label class="mb-1 block text-sm font-semibold">Name</label>
                <input type="text" wire:model="name" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Email</label>
                <input type="email" wire:model="email" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('email') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Phone</label>
                <input type="text" wire:model="phone" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">City</label>
                <input type="text" wire:model="city" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-semibold">Address</label>
                <input type="text" wire:model="address_line" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Create customer</button>
            </div>
        </form>
    @endif

    <div class="mt-8 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">City</th>
                    <th class="px-4 py-3">Source</th>
                    <th class="px-4 py-3">Orders</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($customers as $customer)
                    <tr wire:key="cu-{{ $customer->id }}" class="border-t border-border">
                        <td class="px-4 py-3 font-semibold">
                            <a href="{{ route('admin.customers.show', $customer) }}" class="text-primary" wire:navigate>{{ $customer->name }}</a>
                        </td>
                        <td class="px-4 py-3">{{ $customer->email }}</td>
                        <td class="px-4 py-3">{{ $customer->city ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $customer->customer_source->label() }}</td>
                        <td class="px-4 py-3">{{ $customer->orders_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-6 text-muted-foreground">No {{ $tab }} customers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
