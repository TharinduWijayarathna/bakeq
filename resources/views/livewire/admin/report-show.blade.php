<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.reports.index', ['month' => $month]) }}" wire:navigate class="inline-flex items-center gap-1 text-xs font-bold uppercase tracking-wider text-muted-foreground hover:text-primary">
                <x-icon name="arrow-left" class="size-3.5" /> All reports
            </a>
            <h1 class="mt-2 text-3xl">{{ $payload['title'] }}</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted-foreground">{{ $payload['description'] }}</p>
        </div>
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Month</label>
                <input
                    type="month"
                    wire:model.live="month"
                    class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                >
            </div>
            <a
                href="{{ $downloadUrl }}"
                class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2.5 text-xs font-bold uppercase tracking-wider text-primary-foreground"
            >
                <x-icon name="clipboard" class="size-4" />
                Download PDF
            </a>
        </div>
    </div>

    <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($payload['summary'] as $stat)
            <x-admin.panel wire:key="sum-{{ $loop->index }}" class="p-4">
                <p class="text-xs text-muted-foreground">{{ $stat['label'] }}</p>
                <p class="mt-1 text-lg font-bold tabular-nums">{{ $stat['value'] }}</p>
            </x-admin.panel>
        @endforeach
    </div>

    <x-admin.panel class="mt-6 overflow-x-auto p-0">
        <div class="border-b border-border px-5 py-4">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">{{ $payload['period_label'] }}</p>
            <h2 class="mt-1 text-xl">Report detail</h2>
        </div>
        <table class="w-full text-left text-sm">
            <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                <tr>
                    @foreach ($payload['columns'] as $column)
                        <th class="px-4 py-3">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse ($payload['rows'] as $row)
                    <tr wire:key="row-{{ $loop->index }}" class="border-t border-border">
                        @foreach ($row as $cell)
                            <td class="px-4 py-3 {{ $loop->first ? 'font-semibold' : 'tabular-nums' }}">{{ $cell }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($payload['columns']) }}" class="px-4 py-8 text-muted-foreground">
                            No rows for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if ($payload['footnote'])
            <p class="border-t border-border px-5 py-3 text-xs text-muted-foreground">{{ $payload['footnote'] }}</p>
        @endif
    </x-admin.panel>
</div>
