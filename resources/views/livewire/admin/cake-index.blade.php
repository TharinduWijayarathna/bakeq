<div>
    <div class="flex items-center justify-between gap-4">
        <div>
            <p class="font-script text-3xl text-primary">Menu</p>
            <h1 class="mt-1 text-4xl">Cakes</h1>
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
                    <th class="px-4 py-3">Price</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cakes as $cake)
                    <tr wire:key="admin-cake-{{ $cake->id }}" class="border-t border-border">
                        <td class="px-4 py-3 font-semibold">{{ $cake->name }}</td>
                        <td class="px-4 py-3">{{ $cake->category->name }}</td>
                        <td class="px-4 py-3">{{ $cake->formattedPrice() }}</td>
                        <td class="px-4 py-3">{{ $cake->is_active ? 'Active' : 'Hidden' }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('admin.cakes.edit', $cake) }}" class="text-xs font-bold uppercase text-primary">Edit</a>
                            <button type="button" wire:click="delete({{ $cake->id }})" class="ml-3 text-xs font-bold uppercase text-destructive">Delete</button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
