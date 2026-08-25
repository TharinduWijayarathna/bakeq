<div>
    <p class="font-script text-3xl text-primary">Attendance</p>
    <h1 class="mt-1 text-4xl">Shifts</h1>
    <x-flash />
    @error('shift') <p class="mt-3 text-sm text-destructive">{{ $message }}</p> @enderror

    <div class="mt-8 rounded-4xl bg-card p-6 shadow-soft">
        @if ($openShift)
            <p class="text-sm text-muted-foreground">Clocked in since {{ $openShift->clocked_in_at->format('M j, g:i A') }}</p>
            <button type="button" wire:click="clockOut" class="mt-4 rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Clock out</button>
        @else
            <label class="mb-1 block text-sm font-semibold">Notes (optional)</label>
            <input type="text" wire:model="notes" class="w-full max-w-md rounded-2xl border border-input px-4 py-3 text-sm" placeholder="Morning bake…">
            <button type="button" wire:click="clockIn" class="mt-4 rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Clock in</button>
        @endif
    </div>

    <div class="mt-8 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">Staff</th>
                    <th class="px-4 py-3">In</th>
                    <th class="px-4 py-3">Out</th>
                    <th class="px-4 py-3">Notes</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr wire:key="shift-{{ $entry->id }}" class="border-t border-border">
                        <td class="px-4 py-3 font-semibold">{{ $entry->user->name }}</td>
                        <td class="px-4 py-3">{{ $entry->clocked_in_at->format('M j, g:i A') }}</td>
                        <td class="px-4 py-3">{{ $entry->clocked_out_at?->format('M j, g:i A') ?? 'Open' }}</td>
                        <td class="px-4 py-3">{{ $entry->notes ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-muted-foreground">No shifts logged yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
