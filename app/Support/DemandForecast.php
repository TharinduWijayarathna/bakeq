<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use stdClass;

class DemandForecast
{
    /**
     * Simple moving-average forecast of weekly order volume and top flavours.
     *
     * @return array{
     *     lookback_weeks: int,
     *     horizon_weeks: int,
     *     history: list<array{label: string, orders: int, week_start: string}>,
     *     forecast: list<array{label: string, orders: int, week_start: string}>,
     *     average_weekly_orders: float,
     *     top_flavors: list<array{name: string, quantity: int}>,
     *     summary: string,
     *     chart: array{
     *         width: int,
     *         height: int,
     *         plotLeft: int,
     *         polyline: string,
     *         historyPolyline: string,
     *         forecastPolyline: string,
     *         points: list<array{x: float, y: float, label: string, orders: int, kind: string}>,
     *         yLabels: list<array{y: float, text: string}>,
     *         xLabels: list<array{x: float, text: string}>,
     *         hasData: bool
     *     }
     * }
     */
    public static function weekly(int $lookbackWeeks = 4, int $horizonWeeks = 4): array
    {
        $lookbackWeeks = max(1, $lookbackWeeks);
        $horizonWeeks = max(1, min(4, $horizonWeeks));

        $thisWeekStart = now()->startOfWeek();
        $historyStart = $thisWeekStart->copy()->subWeeks($lookbackWeeks);

        $history = [];

        for ($offset = 0; $offset < $lookbackWeeks; $offset++) {
            $weekStart = $historyStart->copy()->addWeeks($offset)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $orders = BakeryAnalytics::orderCountBetween($weekStart, $weekEnd);

            $history[] = [
                'label' => $weekStart->format('M j'),
                'orders' => $orders,
                'week_start' => $weekStart->toDateString(),
            ];
        }

        $average = collect($history)->avg('orders') ?? 0.0;
        $averageRounded = round($average, 1);
        $forecastOrders = (int) max(0, round($average));

        $forecast = [];

        for ($offset = 0; $offset < $horizonWeeks; $offset++) {
            $weekStart = $thisWeekStart->copy()->addWeeks($offset)->startOfWeek();
            $forecast[] = [
                'label' => $weekStart->format('M j'),
                'orders' => $forecastOrders,
                'week_start' => $weekStart->toDateString(),
            ];
        }

        $topFlavors = self::topFlavors($historyStart, $thisWeekStart->copy()->subSecond());
        $summary = self::summary($lookbackWeeks, $horizonWeeks, $averageRounded, $forecastOrders, $topFlavors);

        return [
            'lookback_weeks' => $lookbackWeeks,
            'horizon_weeks' => $horizonWeeks,
            'history' => $history,
            'forecast' => $forecast,
            'average_weekly_orders' => $averageRounded,
            'top_flavors' => $topFlavors,
            'summary' => $summary,
            'chart' => self::chart($history, $forecast),
        ];
    }

    /**
     * Expected cake units over the next N days, using weekly MA / 7.
     */
    public static function expectedUnitsForDays(int $days, int $lookbackWeeks = 4): float
    {
        $forecast = self::weekly($lookbackWeeks, 1);
        $daily = $forecast['average_weekly_orders'] / 7;

        return round($daily * max(0, $days), 2);
    }

    /**
     * Historical cake mix shares for procurement.
     *
     * @return list<array{cake_id: int, name: string, quantity: int, share: float}>
     */
    public static function cakeMix(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $from ??= now()->subWeeks(4)->startOfDay();
        $to ??= now();
        $cancelled = OrderStatus::Cancelled->value;

        /** @var Collection<int, stdClass> $rows */
        $rows = OrderItem::query()
            ->toBase()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', $cancelled)
            ->whereNotNull('order_items.cake_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->select('order_items.cake_id')
            ->selectRaw('max(order_items.name) as name')
            ->selectRaw('sum(order_items.quantity) as quantity')
            ->groupBy('order_items.cake_id')
            ->orderByDesc('quantity')
            ->get();

        $total = max(1, (int) $rows->sum(fn (stdClass $row): int => (int) $row->quantity));

        return array_values($rows->map(fn (stdClass $row): array => [
            'cake_id' => (int) $row->cake_id,
            'name' => (string) $row->name,
            'quantity' => (int) $row->quantity,
            'share' => round(((int) $row->quantity) / $total, 4),
        ])->all());
    }

    /**
     * @return list<array{name: string, quantity: int}>
     */
    private static function topFlavors(CarbonInterface $from, CarbonInterface $to, int $limit = 5): array
    {
        $cancelled = OrderStatus::Cancelled->value;

        $items = OrderItem::query()
            ->with('cakeDesign')
            ->whereHas('order', function ($query) use ($from, $to, $cancelled): void {
                $query->where('status', '!=', $cancelled)
                    ->whereBetween('created_at', [$from, $to]);
            })
            ->get();

        $counts = [];

        foreach ($items as $item) {
            $labels = $item->cakeDesign?->selections['labels'] ?? null;

            if (is_array($labels) && $labels !== []) {
                foreach ($labels as $label) {
                    $name = trim((string) $label);

                    if ($name === '') {
                        continue;
                    }

                    $counts[$name] = ($counts[$name] ?? 0) + $item->quantity;
                }

                continue;
            }

            $name = trim($item->name);

            if ($name === '') {
                continue;
            }

            $counts[$name] = ($counts[$name] ?? 0) + $item->quantity;
        }

        arsort($counts);

        return collect($counts)
            ->take($limit)
            ->map(fn (int $quantity, string $name): array => [
                'name' => $name,
                'quantity' => $quantity,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array{name: string, quantity: int}>  $topFlavors
     */
    private static function summary(
        int $lookbackWeeks,
        int $horizonWeeks,
        float $average,
        int $forecastOrders,
        array $topFlavors,
    ): string {
        $flavorText = $topFlavors === []
            ? 'No clear flavour leaders yet in that window.'
            : 'Recent favourites: '.collect($topFlavors)->take(3)->pluck('name')->implode(', ').'.';

        return sprintf(
            'We average the last %d full weeks of completed (non-cancelled) orders — a simple moving average — then project that same weekly pace across the next %d weeks (~%s orders/week). %s This method is easy to explain: no seasonality model, just recent history carried forward.',
            $lookbackWeeks,
            $horizonWeeks,
            rtrim(rtrim(number_format($average, 1, '.', ''), '0'), '.') ?: (string) $forecastOrders,
            $flavorText,
        );
    }

    /**
     * @param  list<array{label: string, orders: int}>  $history
     * @param  list<array{label: string, orders: int}>  $forecast
     * @return array{
     *     width: int,
     *     height: int,
     *     plotLeft: int,
     *     polyline: string,
     *     historyPolyline: string,
     *     forecastPolyline: string,
     *     points: list<array{x: float, y: float, label: string, orders: int, kind: string}>,
     *     yLabels: list<array{y: float, text: string}>,
     *     xLabels: list<array{x: float, text: string}>,
     *     hasData: bool
     * }
     */
    private static function chart(array $history, array $forecast): array
    {
        $series = [
            ...collect($history)->map(fn (array $point): array => [...$point, 'kind' => 'history'])->all(),
            ...collect($forecast)->map(fn (array $point): array => [...$point, 'kind' => 'forecast'])->all(),
        ];

        $width = 640;
        $height = 220;
        $padLeft = 40;
        $padRight = 16;
        $padTop = 16;
        $padBottom = 28;
        $plotWidth = $width - $padLeft - $padRight;
        $plotHeight = $height - $padTop - $padBottom;
        $count = count($series);
        $orders = array_column($series, 'orders');
        $max = ($orders === [] ? 0 : max($orders)) ?: 1;
        $stepX = $count > 1 ? $plotWidth / ($count - 1) : 0;

        $points = collect($series)->values()->map(function (array $day, int $index) use ($padLeft, $padTop, $plotHeight, $stepX, $max): array {
            $x = $padLeft + ($index * $stepX);
            $y = $padTop + $plotHeight - (($day['orders'] / $max) * $plotHeight);

            return [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'label' => $day['label'],
                'orders' => $day['orders'],
                'kind' => $day['kind'],
            ];
        });

        $historyPoints = $points->filter(fn (array $point): bool => $point['kind'] === 'history')->values();
        $forecastPoints = $points->filter(fn (array $point): bool => $point['kind'] === 'forecast')->values();

        if ($historyPoints->isNotEmpty() && $forecastPoints->isNotEmpty()) {
            $forecastPoints = collect([$historyPoints->last(), ...$forecastPoints->all()])->values();
        }

        $historyPolyline = $historyPoints->map(fn (array $point): string => $point['x'].','.$point['y'])->implode(' ');
        $forecastPolyline = $forecastPoints->map(fn (array $point): string => $point['x'].','.$point['y'])->implode(' ');
        $polyline = $points->map(fn (array $point): string => $point['x'].','.$point['y'])->implode(' ');
        $baseline = $padTop + $plotHeight;

        return [
            'width' => $width,
            'height' => $height,
            'plotLeft' => $padLeft,
            'polyline' => $polyline,
            'historyPolyline' => $historyPolyline,
            'forecastPolyline' => $forecastPolyline,
            'points' => array_values($points->all()),
            'yLabels' => [
                ['y' => $padTop, 'text' => (string) $max],
                ['y' => $padTop + ($plotHeight / 2), 'text' => (string) (int) round($max / 2)],
                ['y' => $baseline, 'text' => '0'],
            ],
            'xLabels' => array_values($points
                ->filter(fn (array $point, int $index): bool => $index % 2 === 0 || $index === $count - 1)
                ->map(fn (array $point): array => [
                    'x' => $point['x'],
                    'text' => $point['label'],
                ])
                ->values()
                ->all()),
            'hasData' => collect($series)->contains(fn (array $day): bool => $day['orders'] > 0),
        ];
    }
}
