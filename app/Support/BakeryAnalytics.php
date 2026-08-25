<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopSetting;
use App\Models\WasteEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use stdClass;

class BakeryAnalytics
{
    /**
     * @return array{
     *     revenue: int,
     *     previous_revenue: int,
     *     revenue_change_percent: float|null,
     *     budget: int,
     *     budget_progress_percent: float|null,
     *     cogs: int,
     *     gross_profit: int,
     *     waste_cost: int,
     *     net_profit: int,
     *     margin_percent: float,
     *     order_count: int,
     *     previous_order_count: int
     * }
     */
    public static function monthSummary(?CarbonInterface $month = null, ?ShopSetting $settings = null): array
    {
        $month ??= now();
        $settings ??= ShopSetting::current();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $prevStart = $start->copy()->subMonth()->startOfMonth();
        $prevEnd = $start->copy()->subMonth()->endOfMonth();

        $revenue = self::revenueBetween($start, $end);
        $previousRevenue = self::revenueBetween($prevStart, $prevEnd);
        $cogs = self::cogsBetween($start, $end);
        $wasteCost = self::wasteBetween($start, $end);
        $grossProfit = $revenue - $cogs;
        $netProfit = $grossProfit - $wasteCost;
        $budget = (int) $settings->monthly_revenue_budget;

        return [
            'revenue' => $revenue,
            'previous_revenue' => $previousRevenue,
            'revenue_change_percent' => self::percentChange($previousRevenue, $revenue),
            'budget' => $budget,
            'budget_progress_percent' => $budget > 0 ? round(($revenue / $budget) * 100, 1) : null,
            'cogs' => $cogs,
            'gross_profit' => $grossProfit,
            'waste_cost' => $wasteCost,
            'net_profit' => $netProfit,
            'margin_percent' => $revenue > 0 ? round(($grossProfit / $revenue) * 100, 1) : 0.0,
            'order_count' => self::orderCountBetween($start, $end),
            'previous_order_count' => self::orderCountBetween($prevStart, $prevEnd),
        ];
    }

    /**
     * @return list<array{label: string, revenue: int, cogs: int, gross_profit: int, margin_percent: float}>
     */
    public static function marginTrend(int $weeks = 8): array
    {
        $end = now()->endOfWeek();
        $start = $end->copy()->subWeeks($weeks - 1)->startOfWeek();
        $points = [];

        for ($offset = 0; $offset < $weeks; $offset++) {
            $weekStart = $start->copy()->addWeeks($offset)->startOfWeek();
            $weekEnd = $weekStart->copy()->endOfWeek();
            $revenue = self::revenueBetween($weekStart, $weekEnd);
            $cogs = self::cogsBetween($weekStart, $weekEnd);
            $gross = $revenue - $cogs;

            $points[] = [
                'label' => $weekStart->format('M j'),
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross_profit' => $gross,
                'margin_percent' => $revenue > 0 ? round(($gross / $revenue) * 100, 1) : 0.0,
            ];
        }

        return $points;
    }

    /**
     * @return array{
     *     best: list<array{name: string, quantity_sold: int, revenue: int}>,
     *     worst: list<array{name: string, quantity_sold: int, revenue: int}>
     * }
     */
    public static function sellers(?CarbonInterface $from = null, ?CarbonInterface $to = null, int $limit = 5): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfMonth();

        $rows = self::sellerRows($from, $to);

        return [
            'best' => array_values($rows->take($limit)->all()),
            'worst' => array_values($rows->sortBy('quantity_sold')->take($limit)->values()->all()),
        ];
    }

    /**
     * @return list<array{name: string, revenue: int, percent: float}>
     */
    public static function revenueByCategory(?CarbonInterface $from = null, ?CarbonInterface $to = null): array
    {
        $from ??= now()->startOfMonth();
        $to ??= now()->endOfMonth();
        $cancelled = OrderStatus::Cancelled->value;

        /** @var Collection<int, stdClass> $rows */
        $rows = OrderItem::query()
            ->toBase()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('cakes', 'cakes.id', '=', 'order_items.cake_id')
            ->leftJoin('categories', 'categories.id', '=', 'cakes.category_id')
            ->where('orders.status', '!=', $cancelled)
            ->whereBetween('orders.created_at', [$from, $to])
            ->selectRaw("coalesce(categories.name, 'Custom / uncategorised') as category_name")
            ->selectRaw('coalesce(sum(order_items.quantity * order_items.unit_price), 0) as revenue')
            ->groupBy('category_name')
            ->orderByDesc('revenue')
            ->get();

        $total = max(1, (int) $rows->sum(fn (stdClass $row): int => (int) $row->revenue));

        return array_values($rows->map(fn (stdClass $row): array => [
            'name' => (string) $row->category_name,
            'revenue' => (int) $row->revenue,
            'percent' => round(((int) $row->revenue / $total) * 100, 1),
        ])->all());
    }

    public static function revenueBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) Order::query()
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereBetween('created_at', [$from, $to])
            ->sum('subtotal');
    }

    public static function orderCountBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return Order::query()
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    public static function wasteBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) WasteEntry::query()
            ->whereBetween('wasted_on', [$from->toDateString(), $to->toDateString()])
            ->sum('cost_impact');
    }

    public static function cogsBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        $cancelled = OrderStatus::Cancelled->value;
        $settings = ShopSetting::current();

        $items = OrderItem::query()
            ->with(['cake.recipes.items.ingredient'])
            ->whereHas('order', function ($query) use ($from, $to, $cancelled): void {
                $query->where('status', '!=', $cancelled)
                    ->whereBetween('created_at', [$from, $to]);
            })
            ->whereNotNull('cake_id')
            ->get();

        $cogs = 0;

        foreach ($items as $item) {
            if ($item->cake === null) {
                continue;
            }

            $costing = CakeCosting::forCake($item->cake, $settings);

            if ($costing === null) {
                continue;
            }

            $cogs += $costing['total_cost'] * $item->quantity;
        }

        return $cogs;
    }

    /**
     * @return Collection<int, array{name: string, quantity_sold: int, revenue: int}>
     */
    private static function sellerRows(CarbonInterface $from, CarbonInterface $to): Collection
    {
        $cancelled = OrderStatus::Cancelled->value;

        return OrderItem::query()
            ->toBase()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', $cancelled)
            ->whereBetween('orders.created_at', [$from, $to])
            ->select('order_items.name')
            ->selectRaw('sum(order_items.quantity) as quantity_sold')
            ->selectRaw('coalesce(sum(order_items.quantity * order_items.unit_price), 0) as revenue')
            ->groupBy('order_items.name')
            ->orderByDesc('quantity_sold')
            ->get()
            ->map(fn (stdClass $row): array => [
                'name' => (string) $row->name,
                'quantity_sold' => (int) $row->quantity_sold,
                'revenue' => (int) $row->revenue,
            ]);
    }

    private static function percentChange(int $previous, int $current): ?float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
