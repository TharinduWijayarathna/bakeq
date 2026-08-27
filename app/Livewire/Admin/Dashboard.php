<?php

namespace App\Livewire\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Cake;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ShopSetting;
use App\Models\User;
use App\Support\BakeryAnalytics;
use App\Support\DemandForecast;
use App\Support\Money;
use App\Support\ProcurementSuggestions;
use Carbon\CarbonInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use stdClass;

#[Layout('layouts.admin')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public string $monthly_budget_rupees = '0';

    public bool $online_payments_enabled = true;

    public function mount(): void
    {
        $settings = ShopSetting::current();
        $this->monthly_budget_rupees = (string) Money::centsToRupees($settings->monthly_revenue_budget);
        $this->online_payments_enabled = $settings->online_payments_enabled;
    }

    public function saveBudget(): void
    {
        $validated = $this->validate([
            'monthly_budget_rupees' => ['required', 'numeric', 'min:0'],
        ]);

        $settings = ShopSetting::current();
        $settings->update([
            'monthly_revenue_budget' => Money::rupeesToCents((float) $validated['monthly_budget_rupees']),
        ]);

        session()->flash('status', 'Monthly revenue budget updated.');
    }

    public function saveOnlinePayments(): void
    {
        $validated = $this->validate([
            'online_payments_enabled' => ['required', 'boolean'],
        ]);

        ShopSetting::current()->update([
            'online_payments_enabled' => $validated['online_payments_enabled'],
        ]);

        session()->flash('status', $validated['online_payments_enabled']
            ? 'Online payments enabled for website checkout.'
            : 'Online payments disabled. Customers can still pay later.');
    }

    public function render(): View
    {
        $from = now()->subDays(13)->startOfDay();
        $stats = $this->orderStats();
        $dailySeries = $this->dailySeries($from);
        $statusCounts = $this->statusCounts();
        $totalOrders = (int) $statusCounts->sum();
        $month = BakeryAnalytics::monthSummary();
        $marginTrend = BakeryAnalytics::marginTrend(8);
        $sellers = BakeryAnalytics::sellers();
        $categories = BakeryAnalytics::revenueByCategory();
        $forecast = DemandForecast::weekly(4, 4);
        $procurement = ProcurementSuggestions::untilSaturday();

        return view('livewire.admin.dashboard', [
            'cakeCount' => Cake::query()->count(),
            'customerCount' => User::query()->where('role', UserRole::Customer)->count(),
            'pendingOrders' => $stats['pending_orders'],
            'todayOrders' => $stats['today_orders'],
            'monthOrders' => $stats['month_orders'],
            'monthRevenue' => Money::format($stats['month_revenue']),
            'totalOrders' => $totalOrders,
            'recentOrders' => Order::query()->with('user')->latest()->take(6)->get(),
            'revenueChart' => $this->revenueChart($dailySeries),
            'statusBreakdown' => $this->statusBreakdown($statusCounts, max($totalOrders, 1)),
            'topCakes' => $this->topCakes(),
            'monthSummary' => [
                ...$month,
                'revenue_formatted' => Money::format($month['revenue']),
                'previous_revenue_formatted' => Money::format($month['previous_revenue']),
                'budget_formatted' => Money::format($month['budget']),
                'cogs_formatted' => Money::format($month['cogs']),
                'gross_profit_formatted' => Money::format($month['gross_profit']),
                'waste_cost_formatted' => Money::format($month['waste_cost']),
                'net_profit_formatted' => Money::format($month['net_profit']),
            ],
            'marginTrendChart' => $this->marginTrendChart($marginTrend),
            'sellers' => $sellers,
            'categories' => $categories,
            'forecast' => $forecast,
            'procurement' => $procurement,
        ]);
    }

    /**
     * @return array{pending_orders: int, today_orders: int, month_orders: int, month_revenue: int}
     */
    private function orderStats(): array
    {
        $cancelled = OrderStatus::Cancelled->value;
        $pending = OrderStatus::Pending->value;
        $monthStart = now()->startOfMonth()->toDateTimeString();
        $todayStart = now()->startOfDay()->toDateTimeString();

        $stats = Order::query()
            ->toBase()
            ->selectRaw(
                'count(case when status = ? then 1 end) as pending_orders,
                 count(case when created_at >= ? then 1 end) as today_orders,
                 count(case when created_at >= ? then 1 end) as month_orders,
                 coalesce(sum(case when status != ? and created_at >= ? then subtotal else 0 end), 0) as month_revenue',
                [$pending, $todayStart, $monthStart, $cancelled, $monthStart],
            )
            ->first();

        return [
            'pending_orders' => (int) ($stats->pending_orders ?? 0),
            'today_orders' => (int) ($stats->today_orders ?? 0),
            'month_orders' => (int) ($stats->month_orders ?? 0),
            'month_revenue' => (int) ($stats->month_revenue ?? 0),
        ];
    }

    /**
     * @return Collection<string, int>
     */
    private function statusCounts(): Collection
    {
        $counts = Order::query()
            ->toBase()
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(OrderStatus::cases())
            ->mapWithKeys(fn (OrderStatus $status): array => [
                $status->value => (int) $counts->get($status->value, 0),
            ]);
    }

    /**
     * @return list<array{date: string, label: string, orders: int, revenue: int}>
     */
    private function dailySeries(CarbonInterface $from): array
    {
        $cancelled = OrderStatus::Cancelled->value;

        /** @var Collection<string, stdClass> $rows */
        $rows = Order::query()
            ->toBase()
            ->selectRaw(
                'date(created_at) as day, count(*) as orders_count, coalesce(sum(case when status != ? then subtotal else 0 end), 0) as revenue',
                [$cancelled],
            )
            ->where('created_at', '>=', $from)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        return array_values(collect(range(0, 13))
            ->map(function (int $offset) use ($from, $rows): array {
                $day = $from->copy()->addDays($offset);
                $key = $day->toDateString();
                $row = $rows->get($key);

                return [
                    'date' => $key,
                    'label' => $day->format('M j'),
                    'orders' => (int) (is_object($row) ? $row->orders_count : 0),
                    'revenue' => (int) (is_object($row) ? $row->revenue : 0),
                ];
            })
            ->all());
    }

    /**
     * @param  list<array{date: string, label: string, orders: int, revenue: int}>  $series
     * @return array{
     *     width: int,
     *     height: int,
     *     polyline: string,
     *     area: string,
     *     points: list<array{x: float, y: float, label: string, revenue: string, orders: int}>,
     *     yLabels: list<array{y: float, text: string}>,
     *     xLabels: list<array{x: float, text: string}>,
     *     plotLeft: int,
     *     hasData: bool
     * }
     */
    private function revenueChart(array $series): array
    {
        $width = 640;
        $height = 240;
        $padLeft = 72;
        $padRight = 16;
        $padTop = 18;
        $padBottom = 32;
        $plotWidth = $width - $padLeft - $padRight;
        $plotHeight = $height - $padTop - $padBottom;
        $count = count($series);
        $revenues = array_column($series, 'revenue');
        $max = ($revenues === [] ? 0 : max($revenues)) ?: 1;
        $stepX = $count > 1 ? $plotWidth / ($count - 1) : 0;

        $points = collect($series)->values()->map(function (array $day, int $index) use ($padLeft, $padTop, $plotHeight, $stepX, $max): array {
            $x = $padLeft + ($index * $stepX);
            $y = $padTop + $plotHeight - (($day['revenue'] / $max) * $plotHeight);

            return [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'label' => $day['label'],
                'revenue' => Money::format($day['revenue']),
                'orders' => $day['orders'],
            ];
        });

        $polyline = $points->map(fn (array $point): string => $point['x'].','.$point['y'])->implode(' ');
        $first = $points->first();
        $last = $points->last();
        $baseline = $padTop + $plotHeight;
        $area = $first && $last
            ? 'M '.$first['x'].','.$baseline.' L '.$polyline.' L '.$last['x'].','.$baseline.' Z'
            : '';

        return [
            'width' => $width,
            'height' => $height,
            'plotLeft' => $padLeft,
            'polyline' => $polyline,
            'area' => $area,
            'points' => array_values($points->all()),
            'yLabels' => [
                ['y' => $padTop, 'text' => Money::format($max)],
                ['y' => $padTop + ($plotHeight / 2), 'text' => Money::format((int) round($max / 2))],
                ['y' => $baseline, 'text' => Money::format(0)],
            ],
            'xLabels' => array_values($points
                ->filter(fn (array $point, int $index): bool => $index % 2 === 0 || $index === $count - 1)
                ->map(fn (array $point): array => [
                    'x' => $point['x'],
                    'text' => $point['label'],
                ])
                ->values()
                ->all()),
            'hasData' => collect($series)->contains(fn (array $day): bool => $day['revenue'] > 0 || $day['orders'] > 0),
        ];
    }

    /**
     * @param  list<array{label: string, revenue: int, margin_percent: float}>  $trend
     * @return array{
     *     width: int,
     *     height: int,
     *     plotLeft: int,
     *     polyline: string,
     *     points: list<array{x: float, y: float, label: string, margin: string}>,
     *     yLabels: list<array{y: float, text: string}>,
     *     xLabels: list<array{x: float, text: string}>,
     *     hasData: bool
     * }
     */
    private function marginTrendChart(array $trend): array
    {
        $width = 640;
        $height = 180;
        $padLeft = 40;
        $padRight = 16;
        $padTop = 14;
        $padBottom = 28;
        $plotWidth = $width - $padLeft - $padRight;
        $plotHeight = $height - $padTop - $padBottom;
        $count = count($trend);
        $margins = array_column($trend, 'margin_percent');
        $max = max(1.0, ...($margins === [] ? [1.0] : $margins));
        $stepX = $count > 1 ? $plotWidth / ($count - 1) : 0;

        $points = collect($trend)->values()->map(function (array $week, int $index) use ($padLeft, $padTop, $plotHeight, $stepX, $max): array {
            $x = $padLeft + ($index * $stepX);
            $y = $padTop + $plotHeight - ((max(0, $week['margin_percent']) / $max) * $plotHeight);

            return [
                'x' => round($x, 2),
                'y' => round($y, 2),
                'label' => $week['label'],
                'margin' => $week['margin_percent'].'%',
            ];
        });

        $polyline = $points->map(fn (array $point): string => $point['x'].','.$point['y'])->implode(' ');
        $baseline = $padTop + $plotHeight;

        return [
            'width' => $width,
            'height' => $height,
            'plotLeft' => $padLeft,
            'polyline' => $polyline,
            'points' => array_values($points->all()),
            'yLabels' => [
                ['y' => $padTop, 'text' => round($max, 0).'%'],
                ['y' => $baseline, 'text' => '0%'],
            ],
            'xLabels' => array_values($points
                ->filter(fn (array $point, int $index): bool => $index % 2 === 0 || $index === $count - 1)
                ->map(fn (array $point): array => [
                    'x' => $point['x'],
                    'text' => $point['label'],
                ])
                ->values()
                ->all()),
            'hasData' => collect($trend)->contains(fn (array $week): bool => $week['margin_percent'] != 0.0 || $week['revenue'] > 0),
        ];
    }

    /**
     * @param  Collection<string, int>  $counts
     * @return list<array{label: string, count: int, percent: float, color: string, dash: string, offset: float}>
     */
    private function statusBreakdown(Collection $counts, int $total): array
    {
        $radius = 38;
        $circumference = 2 * M_PI * $radius;
        $offset = 0.0;
        $segments = [];

        foreach (OrderStatus::cases() as $status) {
            $count = (int) $counts->get($status->value, 0);
            $length = ($count / $total) * $circumference;
            $segments[] = [
                'label' => $status->label(),
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
                'color' => $status->color(),
                'dash' => round($length, 2).' '.round($circumference, 2),
                'offset' => round(-$offset, 2),
            ];
            $offset += $length;
        }

        return $segments;
    }

    /**
     * @return list<array{name: string, quantity_sold: int, percent: int}>
     */
    private function topCakes(): array
    {
        $rows = OrderItem::query()
            ->toBase()
            ->select('name')
            ->selectRaw('sum(quantity) as quantity_sold')
            ->whereIn(
                'order_id',
                Order::query()->where('status', '!=', OrderStatus::Cancelled)->select('id'),
            )
            ->groupBy('name')
            ->orderByDesc('quantity_sold')
            ->limit(5)
            ->get();

        $maxSold = max([1, ...$rows->map(fn (stdClass $row): int => (int) $row->quantity_sold)->all()]);

        return array_values($rows
            ->map(fn (stdClass $row): array => [
                'name' => (string) $row->name,
                'quantity_sold' => (int) $row->quantity_sold,
                'percent' => max(8, (int) round(((int) $row->quantity_sold / $maxSold) * 100)),
            ])
            ->all());
    }
}
