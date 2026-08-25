<div>
    <a href="{{ route('admin.cakes.index') }}" class="text-sm font-semibold text-muted-foreground">Back to cakes</a>
    <h1 class="mt-2 text-4xl">{{ $cake?->exists ? 'Edit cake' : 'Add cake' }}</h1>
    <x-flash />

    <form wire:submit="save" class="mt-8 max-w-3xl space-y-4 rounded-4xl bg-card p-6 shadow-soft">
        <div>
            <label class="mb-1 block text-sm font-semibold">Name</label>
            <input type="text" wire:model="name" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            @error('name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Category</label>
            <select wire:model="category_id" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                <option value="">Choose</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold">Catalog price (Rs.)</label>
                <input type="number" wire:model="price_rupees" min="0" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('price_rupees') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Base price (Rs.)</label>
                <input type="number" wire:model="base_price_rupees" min="0" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('base_price_rupees') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Per-tier add-on (Rs.)</label>
                <input type="number" wire:model="per_tier_addon_rupees" min="0" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('per_tier_addon_rupees') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Per-flavour add-on (Rs.)</label>
                <input type="number" wire:model="per_flavor_addon_rupees" min="0" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('per_flavor_addon_rupees') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Serves (summary)</label>
                <input type="text" wire:model="serves" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Lead time (days)</label>
                <input type="number" wire:model="lead_days" min="0" max="60" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('lead_days') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="mb-1 block text-sm font-semibold">Note</label>
            <input type="text" wire:model="note" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Description</label>
            <textarea wire:model="description" rows="3" class="w-full rounded-2xl border border-input px-4 py-3 text-sm"></textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Care instructions</label>
            <textarea wire:model="care_instructions" rows="3" class="w-full rounded-2xl border border-input px-4 py-3 text-sm"></textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Ingredients (one per line)</label>
            <textarea wire:model="ingredients_text" rows="4" class="w-full rounded-2xl border border-input px-4 py-3 text-sm" placeholder="Flour&#10;Sugar&#10;Eggs"></textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Allergens (comma-separated)</label>
            <input type="text" wire:model="allergens_text" class="w-full rounded-2xl border border-input px-4 py-3 text-sm" placeholder="Gluten, Eggs, Dairy">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Size options</label>
            <p class="mb-1 text-xs text-muted-foreground">One per line: label|servings|price in Rs. Example: <code>1 kg|8-10|4500</code></p>
            <textarea wire:model="size_options_text" rows="3" class="w-full rounded-2xl border border-input px-4 py-3 font-mono text-sm"></textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Optional add-ons</label>
            <p class="mb-1 text-xs text-muted-foreground">One per line: name|price in Rs. Example: <code>Fresh florals|1200</code></p>
            <textarea wire:model="optional_addons_text" rows="3" class="w-full rounded-2xl border border-input px-4 py-3 font-mono text-sm"></textarea>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Photo</label>
            <input type="file" wire:model="image" accept="image/*" class="w-full text-sm">
            @error('image') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active"> Active</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_featured"> Featured on home</label>
        <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Save cake</button>
    </form>
</div>
