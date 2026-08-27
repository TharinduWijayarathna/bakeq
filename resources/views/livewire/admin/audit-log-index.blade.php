<div>
    <p class="font-script text-3xl text-primary">Trail</p>
    <h1 class="mt-1 text-4xl">Audit log</h1>

    <div class="mt-6 flex flex-wrap gap-3">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Search action or user" class="min-w-56 flex-1 rounded-2xl border border-input px-4 py-2.5 text-sm">
        <select wire:model.live="action" class="rounded-2xl border border-input px-4 py-2.5 text-sm">
            <option value="">All actions</option>
            @foreach ($actions as $actionOption)
                <option value="{{ $actionOption }}">{{ $actionOption }}</option>
            @endforeach
        </select>
    </div>

    <div class="mt-8 overflow-x-auto rounded-4xl bg-card shadow-soft">
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Who</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Old → New</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr wire:key="audit-{{ $log->id }}" class="border-t border-border align-top">
                        <td class="px-4 py-3 whitespace-nowrap">{{ $log->created_at?->format('M j, g:i A') }}</td>
                        <td class="px-4 py-3">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $log->action }}</td>
                        <td class="px-4 py-3">
                            <x-admin.audit-diff :old="$log->old_values" :new="$log->new_values" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-6 text-muted-foreground">No audit entries yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
