<div>
    <p class="font-script text-3xl text-primary">Losses</p>
    <h1 class="mt-1 text-4xl">Waste log</h1>
    <x-flash />

    <div class="mt-6 rounded-4xl bg-secondary p-5">
        <p class="text-sm text-muted-foreground">Running waste cost</p>
        <p class="text-3xl font-bold text-primary">{{ \App\Support\Money::format($totalCost) }}</p>
    </div>

    <form wire:submit="save" class="mt-6 grid gap-4 rounded-4xl bg-card p-6 shadow-soft lg:grid-cols-3">
        <div>
            <label class="mb-1 block text-sm font-semibold">Date</label>
            <input type="date" wire:model="wasted_on" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Type</label>
            <select wire:model.live="item_type" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                <option value="ingredient">Ingredient</option>
                <option value="cake">Cake</option>
            </select>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Reason</label>
            <select wire:model="reason" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                @foreach ($reasons as $reasonOption)
                    <option value="{{ $reasonOption->value }}">{{ $reasonOption->label() }}</option>
                @endforeach
            </select>
        </div>
        @if ($item_type === 'ingredient')
            <div class="lg:col-span-2">
                <label class="mb-1 block text-sm font-semibold">Ingredient</label>
                <select wire:model="ingredient_id" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                    <option value="">Choose</option>
                    @foreach ($ingredients as $ingredient)
                        <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit->value }})</option>
                    @endforeach
                </select>
                @error('ingredient_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
        @else
            <div class="lg:col-span-2">
                <label class="mb-1 block text-sm font-semibold">Cake</label>
                <select wire:model="cake_id" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                    <option value="">Choose</option>
                    @foreach ($cakes as $cake)
                        <option value="{{ $cake->id }}">{{ $cake->name }}</option>
                    @endforeach
                </select>
                @error('cake_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
        @endif
        <div>
            <label class="mb-1 block text-sm font-semibold">Quantity</label>
            <input type="number" step="0.001" min="0" wire:model="quantity" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            @error('quantity') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div class="lg:col-span-3">
            <label class="mb-1 block text-sm font-semibold">Notes</label>
            <input type="text" wire:model="notes" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
        </div>
        <div class="lg:col-span-3">
            <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Log waste</button>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Item</th>
                    <th class="px-4 py-3">Qty</th>
                    <th class="px-4 py-3">Reason</th>
                    <th class="px-4 py-3">Cost impact</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr wire:key="waste-{{ $entry->id }}" class="border-t border-border">
                        <td class="px-4 py-3">{{ $entry->wasted_on->toFormattedDateString() }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $entry->label() }}</td>
                        <td class="px-4 py-3">{{ rtrim(rtrim(number_format((float) $entry->quantity, 3, '.', ''), '0'), '.') }}</td>
                        <td class="px-4 py-3">{{ $entry->reason->label() }}</td>
                        <td class="px-4 py-3">{{ $entry->formattedCostImpact() }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="delete({{ $entry->id }})" class="text-xs font-bold uppercase text-destructive">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-muted-foreground">No waste logged yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
