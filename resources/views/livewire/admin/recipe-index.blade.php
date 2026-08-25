<div>
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="font-script text-3xl text-primary">Kitchen</p>
            <h1 class="mt-1 text-4xl">Recipes</h1>
        </div>
        <a href="{{ route('admin.recipes.create') }}" class="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground">Add recipe</a>
    </div>
    <x-flash />

    <div class="mt-8 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Recipe</th>
                    <th class="px-4 py-3">Cake</th>
                    <th class="px-4 py-3">Lines</th>
                    <th class="px-4 py-3">Cost</th>
                    <th class="px-4 py-3">Sale</th>
                    <th class="px-4 py-3">Margin</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recipes as $row)
                    <tr wire:key="recipe-{{ $row['recipe']->id }}" class="border-t border-border">
                        <td class="px-4 py-3 font-semibold">{{ $row['recipe']->displayName() }}</td>
                        <td class="px-4 py-3">{{ $row['recipe']->cake->name }}</td>
                        <td class="px-4 py-3">{{ $row['recipe']->items->count() }}</td>
                        <td class="px-4 py-3">{{ $row['formatted_cost'] }}</td>
                        <td class="px-4 py-3">{{ $row['formatted_sale'] }}</td>
                        <td class="px-4 py-3">
                            {{ $row['costing']['margin_percent'] }}%
                            <span class="text-muted-foreground">({{ $row['formatted_profit'] }})</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.recipes.edit', $row['recipe']) }}" class="text-xs font-bold uppercase text-primary">Edit</a>
                            <button type="button" wire:click="delete({{ $row['recipe']->id }})" class="ml-3 text-xs font-bold uppercase text-destructive">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-muted-foreground">No recipes yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
