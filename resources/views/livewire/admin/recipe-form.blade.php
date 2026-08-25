<div>
    <a href="{{ route('admin.recipes.index') }}" class="text-sm font-semibold text-muted-foreground">Back to recipes</a>
    <h1 class="mt-2 text-4xl">{{ $recipe?->exists ? 'Edit recipe' : 'Add recipe' }}</h1>
    <x-flash />

    @if ($formattedPreview)
        <div class="mt-6 grid gap-3 rounded-4xl bg-secondary p-5 text-sm sm:grid-cols-4">
            <div><p class="text-xs uppercase text-muted-foreground">Cost</p><p class="font-bold">{{ $formattedPreview['cost'] }}</p></div>
            <div><p class="text-xs uppercase text-muted-foreground">Sale price</p><p class="font-bold">{{ $formattedPreview['sale'] }}</p></div>
            <div><p class="text-xs uppercase text-muted-foreground">Profit</p><p class="font-bold">{{ $formattedPreview['profit'] }}</p></div>
            <div><p class="text-xs uppercase text-muted-foreground">Margin</p><p class="font-bold">{{ $formattedPreview['margin'] }}%</p></div>
        </div>
    @endif

    <form wire:submit="save" class="mt-8 space-y-4 rounded-4xl bg-card p-6 shadow-soft">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold">Cake</label>
                <select wire:model="cake_id" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                    <option value="">Choose cake</option>
                    @foreach ($cakes as $cake)
                        <option value="{{ $cake->id }}">{{ $cake->name }}</option>
                    @endforeach
                </select>
                @error('cake_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Size label (optional)</label>
                <input type="text" wire:model="size_label" class="w-full rounded-2xl border border-input px-4 py-3 text-sm" placeholder="e.g. 1 kg">
                @error('size_label') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1 block text-sm font-semibold">Recipe name (optional)</label>
                <input type="text" wire:model="name" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            </div>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-lg font-bold">Ingredients</h2>
                <button type="button" wire:click="addLine" class="rounded-full bg-muted px-4 py-2 text-xs font-bold uppercase">Add line</button>
            </div>
            @error('lines') <p class="text-sm text-destructive">{{ $message }}</p> @enderror

            <div>
                <label class="mb-1 block text-xs font-bold uppercase">Search ingredients</label>
                <input type="search" wire:model.live.debounce.250ms="ingredient_search" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm" placeholder="Type to filter the picker…">
            </div>

            @foreach ($lines as $index => $line)
                <div wire:key="line-{{ $index }}" class="grid gap-3 rounded-3xl bg-muted/50 p-4 sm:grid-cols-[1fr_8rem_auto]">
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase">Ingredient</label>
                        <select wire:model="lines.{{ $index }}.ingredient_id" class="w-full rounded-2xl border border-input bg-background px-4 py-2.5 text-sm">
                            <option value="">Choose</option>
                            @foreach ($ingredients as $ingredient)
                                <option value="{{ $ingredient->id }}">{{ $ingredient->name }} ({{ $ingredient->unit->value }})</option>
                            @endforeach
                        </select>
                        @error('lines.'.$index.'.ingredient_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror

                        @if ($ingredient_search !== '')
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($ingredients as $ingredient)
                                    <button type="button" wire:click="pickIngredient({{ $index }}, {{ $ingredient->id }})" class="rounded-full bg-card px-3 py-1 text-xs font-semibold shadow-soft">
                                        {{ $ingredient->name }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-bold uppercase">Qty / cake</label>
                        <input type="number" step="0.001" min="0" wire:model="lines.{{ $index }}.quantity" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
                        @error('lines.'.$index.'.quantity') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex items-end">
                        <button type="button" wire:click="removeLine({{ $index }})" class="rounded-full px-3 py-2 text-xs font-bold uppercase text-destructive">Remove</button>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Save recipe</button>
    </form>
</div>
