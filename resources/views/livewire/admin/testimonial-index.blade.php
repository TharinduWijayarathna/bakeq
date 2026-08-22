<div>
    <p class="font-script text-3xl text-primary">Storefront</p>
    <h1 class="mt-1 text-4xl">Testimonials</h1>
    <p class="mt-2 max-w-xl text-sm text-muted-foreground">These quotes appear in the sliding review section on the home page. Hidden testimonials stay in the list but are not shown on the site.</p>
    <x-flash />

    <form wire:submit="save" class="mt-8 space-y-4 rounded-4xl bg-card p-5 shadow-soft">
        <div>
            <label class="mb-1 block text-xs font-bold uppercase">Quote</label>
            <textarea wire:model="quote" rows="3" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm"></textarea>
            @error('quote') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-48 flex-1">
                <label class="mb-1 block text-xs font-bold uppercase">Name</label>
                <input type="text" wire:model="author" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
                @error('author') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div class="min-w-40 flex-1">
                <label class="mb-1 block text-xs font-bold uppercase">Occasion</label>
                <input type="text" wire:model="occasion" placeholder="Birthday order" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
                @error('occasion') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div class="w-24">
                <label class="mb-1 block text-xs font-bold uppercase">Stars</label>
                <input type="number" min="1" max="5" wire:model="rating" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
                @error('rating') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div class="w-24">
                <label class="mb-1 block text-xs font-bold uppercase">Sort</label>
                <input type="number" min="0" wire:model="sort" class="w-full rounded-2xl border border-input px-4 py-2.5 text-sm">
                @error('sort') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-primary-foreground">
                {{ $editingId ? 'Update' : 'Add testimonial' }}
            </button>
            @if ($editingId)
                <button type="button" wire:click="cancel" class="rounded-full bg-muted px-5 py-2.5 text-sm font-bold">Cancel</button>
            @endif
        </div>
    </form>

    <div class="mt-6 overflow-hidden rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Quote</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($testimonials as $testimonial)
                    <tr wire:key="testimonial-{{ $testimonial->id }}" class="border-t border-border">
                        <td class="max-w-md px-4 py-3">
                            <p class="line-clamp-2">{{ $testimonial->quote }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ $testimonial->rating }} stars @if ($testimonial->occasion) · {{ $testimonial->occasion }} @endif</p>
                        </td>
                        <td class="px-4 py-3 font-semibold">{{ $testimonial->author }}</td>
                        <td class="px-4 py-3">{{ $testimonial->is_active ? 'Visible' : 'Hidden' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button type="button" wire:click="edit({{ $testimonial->id }})" class="text-xs font-bold uppercase text-primary">Edit</button>
                            <button type="button" wire:click="toggle({{ $testimonial->id }})" class="ml-3 text-xs font-bold uppercase">Toggle</button>
                            <button type="button" wire:click="delete({{ $testimonial->id }})" class="ml-3 text-xs font-bold uppercase text-destructive">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">No testimonials yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
