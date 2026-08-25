<div>
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="font-script text-3xl text-primary">Menu</p>
            <h1 class="mt-1 text-4xl">Cakes</h1>
            <p class="mt-1 text-sm text-muted-foreground">Cost includes {{ rtrim(rtrim(number_format((float) $laborPercent, 2), '0'), '.') }}% labor / overhead.</p>
        </div>
        <a href="{{ route('admin.cakes.create') }}" class="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground">Add cake</a>
    </div>
    <x-flash />

    <div class="mt-8 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Cake</th>
                    <th class="px-4 py-3">Category</th>
                    <th class="px-4 py-3">Sale</th>
                    <th class="px-4 py-3">Cost</th>
                    <th class="px-4 py-3">Margin</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cakes as $row)
                    <tr wire:key="admin-cake-{{ $row['cake']->id }}" class="border-t border-border">
                        <td class="px-4 py-3 font-semibold">{{ $row['cake']->name }}</td>
                        <td class="px-4 py-3">{{ $row['cake']->category->name }}</td>
                        <td class="px-4 py-3">{{ $row['cake']->formattedPrice() }}</td>
                        <td class="px-4 py-3">{{ $row['formatted_cost'] ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($row['costing'])
                                {{ $row['margin_percent'] }}%
                                <span class="text-muted-foreground">({{ $row['formatted_profit'] }})</span>
                            @else
                                <span class="text-muted-foreground">No recipe</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $row['cake']->is_active ? 'Active' : 'Hidden' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.cakes.edit', $row['cake']) }}" class="text-xs font-bold uppercase text-primary">Edit</a>
                            <button type="button" wire:click="delete({{ $row['cake']->id }})" class="ml-3 text-xs font-bold uppercase text-destructive">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
