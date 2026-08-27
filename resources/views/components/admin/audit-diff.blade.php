@props([
    'old' => null,
    'new' => null,
])

@php
    /** @var array<string, mixed> $oldValues */
    $oldValues = is_array($old) ? $old : [];
    /** @var array<string, mixed> $newValues */
    $newValues = is_array($new) ? $new : [];

    $formatValue = static function (mixed $value) use (&$formatValue): string {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            if ($value === []) {
                return '—';
            }

            return collect($value)
                ->map(function (mixed $item, mixed $key) use (&$formatValue): string {
                    $formatted = $formatValue($item);

                    return is_int($key) ? $formatted : str_replace('_', ' ', (string) $key).': '.$formatted;
                })
                ->implode(', ');
        }

        return (string) $value;
    };

    $keys = collect([...array_keys($oldValues), ...array_keys($newValues)])
        ->unique()
        ->sort()
        ->values();
@endphp

@if ($keys->isEmpty())
    <span class="text-muted-foreground">No changes recorded</span>
@else
    <div {{ $attributes->class('min-w-56 space-y-2') }}>
        @foreach ($keys as $key)
            @php
                $hasOld = array_key_exists($key, $oldValues);
                $hasNew = array_key_exists($key, $newValues);
                $oldDisplay = $hasOld ? $formatValue($oldValues[$key]) : '—';
                $newDisplay = $hasNew ? $formatValue($newValues[$key]) : '—';
                $changed = ! $hasOld || ! $hasNew || $oldDisplay !== $newDisplay;
            @endphp
            <div class="rounded-2xl bg-muted/60 px-3 py-2">
                <p class="text-[10px] font-bold uppercase tracking-wider text-muted-foreground">{{ str_replace('_', ' ', $key) }}</p>
                <div class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                    @if ($hasOld && ! $hasNew)
                        <span class="font-medium text-muted-foreground line-through decoration-border">{{ $oldDisplay }}</span>
                        <span class="text-muted-foreground" aria-hidden="true">→</span>
                        <span class="font-semibold text-destructive">removed</span>
                    @elseif (! $hasOld && $hasNew)
                        <span class="font-semibold text-foreground">{{ $newDisplay }}</span>
                    @elseif ($changed)
                        <span class="text-muted-foreground line-through decoration-border">{{ $oldDisplay }}</span>
                        <span class="text-muted-foreground" aria-hidden="true">→</span>
                        <span class="font-semibold text-foreground">{{ $newDisplay }}</span>
                    @else
                        <span class="font-medium text-foreground">{{ $newDisplay }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif
