<div>
    <p class="font-script text-3xl text-primary">Catalog</p>
    <h1 class="mt-1 text-4xl">Cake categories</h1>
    <x-flash />

    <form wire:submit="save" class="mt-8 flex flex-wrap items-end gap-3 rounded-4xl bg-card p-5 shadow-soft">
        <div class="min-w-48 flex-1">
            <label class="mb-1 block text-xs font-bold uppercase">Name</label>
            <input type="text" wire:model="name" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
            @error('name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div class="w-28">
            <label class="mb-1 block text-xs font-bold uppercase">Sort</label>
            <input type="number" wire:model="sort" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
        </div>
        <button type="submit" class="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground">
            {{ $editingId ? 'Update' : 'Add category' }}
        </button>
    </form>

    <div class="mt-6 overflow-hidden rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Cakes</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($categories as $category)
                    <tr wire:key="cat-{{ $category->id }}" class="border-t border-border">
                        <td class="px-4 py-3 font-semibold">{{ $category->name }}</td>
                        <td class="px-4 py-3">{{ $category->cakes_count }}</td>
                        <td class="px-4 py-3">{{ $category->is_active ? 'Active' : 'Hidden' }}</td>
                        <td class="px-4 py-3 text-right">
                            <button type="button" wire:click="edit({{ $category->id }})" class="text-xs font-bold uppercase text-primary">Edit</button>
                            <button type="button" wire:click="toggle({{ $category->id }})" class="ml-3 text-xs font-bold uppercase">Toggle</button>
                            <button type="button" wire:click="delete({{ $category->id }})" class="ml-3 text-xs font-bold uppercase text-destructive">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
