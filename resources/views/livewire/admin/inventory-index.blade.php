<div>
    <p class="font-script text-3xl text-primary">Stock</p>
    <h1 class="mt-1 text-4xl">Inventory</h1>
    <x-flash />

    @if ($lowStock->isNotEmpty())
        <div class="mt-6 rounded-4xl border border-destructive/30 bg-destructive/10 px-5 py-4 text-sm text-destructive">
            <p class="font-bold">Low stock alert</p>
            <ul class="mt-2 space-y-1">
                @foreach ($lowStock as $item)
                    <li wire:key="low-{{ $item->id }}">{{ $item->name }} — {{ $item->stockLabel() }} (reorder at {{ rtrim(rtrim(number_format((float) $item->reorder_threshold, 3, '.', ''), '0'), '.') }} {{ $item->unit->value }})</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($expiringSoon->isNotEmpty())
        <div class="mt-4 rounded-4xl border border-primary/20 bg-primary/10 px-5 py-4 text-sm text-primary">
            <p class="font-bold">Expiring soon (14 days)</p>
            <ul class="mt-2 space-y-1">
                @foreach ($expiringSoon as $item)
                    <li wire:key="exp-{{ $item->id }}">{{ $item->name }} — {{ $item->expiry_date->toFormattedDateString() }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form wire:submit="saveLaborOverhead" class="mt-6 flex flex-wrap items-end gap-3 rounded-4xl bg-card p-5 shadow-soft">
        <div>
            <label class="mb-1 block text-xs font-bold uppercase">Labor / overhead %</label>
            <input type="number" step="0.01" min="0" max="100" wire:model="labor_overhead_percent" class="w-32 rounded-2xl border border-input px-4 py-2.5 text-sm">
            @error('labor_overhead_percent') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="rounded-full bg-secondary px-5 py-2.5 text-sm font-bold text-secondary-foreground">Save costing %</button>
        <p class="w-full text-xs text-muted-foreground">Cake cost = ingredient cost + this overhead %. Used on Cakes and Recipes pages.</p>
    </form>

    <form wire:submit="save" class="mt-6 grid gap-3 rounded-4xl bg-card p-5 shadow-soft lg:grid-cols-3">
        <h2 class="text-lg font-bold lg:col-span-3">{{ $editingId ? 'Edit ingredient' : 'Add ingredient' }}</h2>
        <div>
            <label class="mb-1 block text-xs font-bold uppercase">Name</label>
            <input type="text" wire:model="name" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
            @error('name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold uppercase">Unit</label>
            <select wire:model="unit" class="w-full rounded-2xl border border-input bg-background px-4 py-2.5 text-sm">
                @foreach ($units as $unitOption)
                    <option value="{{ $unitOption->value }}">{{ $unitOption->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold uppercase">Current stock</label>
            <input type="number" step="0.001" min="0" wire:model="current_stock" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
            @error('current_stock') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold uppercase">Unit cost (Rs.)</label>
            <input type="number" step="0.01" min="0" wire:model="unit_cost_rupees" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
            @error('unit_cost_rupees') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold uppercase">Reorder threshold</label>
            <input type="number" step="0.001" min="0" wire:model="reorder_threshold" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold uppercase">Supplier</label>
            <input type="text" wire:model="supplier" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-bold uppercase">Expiry date</label>
            <input type="date" wire:model="expiry_date" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
        </div>
        <div class="flex items-end gap-2 lg:col-span-2">
            <button type="submit" class="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground">
                {{ $editingId ? 'Update' : 'Add ingredient' }}
            </button>
            @if ($editingId)
                <button type="button" wire:click="resetForm" class="rounded-full bg-muted px-5 py-2.5 text-sm font-bold">Cancel</button>
            @endif
        </div>
    </form>

    <div class="mt-6">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search ingredients or suppliers…" class="w-full max-w-md rounded-2xl border border-input bg-card px-4 py-2.5 text-sm shadow-soft">
    </div>

    <div class="mt-4 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Ingredient</th>
                    <th class="px-4 py-3">Stock</th>
                    <th class="px-4 py-3">Unit cost</th>
                    <th class="px-4 py-3">Supplier</th>
                    <th class="px-4 py-3">Expiry</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ingredients as $ingredient)
                    <tr wire:key="ing-{{ $ingredient->id }}" class="border-t border-border">
                        <td class="px-4 py-3 font-semibold">
                            {{ $ingredient->name }}
                            @if ($ingredient->isLowStock())
                                <span class="ml-2 rounded-full bg-destructive/10 px-2 py-0.5 text-[10px] font-bold uppercase text-destructive">Low</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $ingredient->stockLabel() }}</td>
                        <td class="px-4 py-3">{{ $ingredient->formattedUnitCost() }}</td>
                        <td class="px-4 py-3">{{ $ingredient->supplier ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $ingredient->expiry_date?->toFormattedDateString() ?? '—' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="edit({{ $ingredient->id }})" class="text-xs font-bold uppercase text-primary">Edit</button>
                            <button type="button" wire:click="delete({{ $ingredient->id }})" class="ml-3 text-xs font-bold uppercase text-destructive">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-muted-foreground">No ingredients yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
