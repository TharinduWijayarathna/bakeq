<div>
    <a href="{{ route('admin.cakes.index') }}" class="text-sm font-semibold text-muted-foreground">Back to cakes</a>
    <h1 class="mt-2 text-4xl">{{ $cake?->exists ? 'Edit cake' : 'Add cake' }}</h1>
    <x-flash />

    <form wire:submit="save" class="mt-8 max-w-2xl space-y-4 rounded-4xl bg-card p-6 shadow-soft">
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
        <div>
            <label class="mb-1 block text-sm font-semibold">Price (Rs.)</label>
            <input type="number" wire:model="price_rupees" min="0" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            @error('price_rupees') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Serves</label>
            <input type="text" wire:model="serves" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
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
            <label class="mb-1 block text-sm font-semibold">Photo</label>
            <input type="file" wire:model="image" accept="image/*" class="w-full text-sm">
            @error('image') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_active"> Active</label>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="is_featured"> Featured on home</label>
        <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Save cake</button>
    </form>
</div>
