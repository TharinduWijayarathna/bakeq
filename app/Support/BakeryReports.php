<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductionStatus;
use App\Enums\ReportType;
use App\Enums\UserRole;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Shift;
use App\Models\User;
use App\Models\WasteEntry;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use stdClass;

class BakeryReports
{
    /**
     * Hub KPIs for the selected month.
     *
     * @return array{
     *     month_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     cakes_sold: int,
     *     ingredient_kinds: int,
     *     ingredient_cost: int,
     *     revenue: int,
     *     paid_earnings: int,
     *     outstanding: int,
     *     cogs: int,
     *     waste_cost: int,
     *     gross_profit: int,
     *     net_profit: int,
     *     order_count: int,
     *     cancelled_orders: int
     * }
     */
    public static function monthOverview(?CarbonInterface $month = null): array
    {
        $month ??= now();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $usage = self::ingredientUsage($from, $to);
        $financial = BakeryAnalytics::monthSummary($month);
        $paid = self::paidEarningsBetween($from, $to);
        $outstanding = max(0, $financial['revenue'] - $paid);

        return [
            'month_label' => $from->format('F Y'),
            'from' => $from,
            'to' => $to,
            'cakes_sold' => self::cakesSoldBetween($from, $to),
            'ingredient_kinds' => count($usage),
            'ingredient_cost' => (int) collect($usage)->sum('cost'),
            'revenue' => $financial['revenue'],
            'paid_earnings' => $paid,
            'outstanding' => $outstanding,
            'cogs' => $financial['cogs'],
            'waste_cost' => $financial['waste_cost'],
            'gross_profit' => $financial['gross_profit'],
            'net_profit' => $financial['net_profit'],
            'order_count' => $financial['order_count'],
            'cancelled_orders' => self::cancelledOrderCountBetween($from, $to),
        ];
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    public static function build(ReportType $type, ?CarbonInterface $month = null): array
    {
        $month ??= now();
        $from = $month->copy()->startOfMonth();
        $to = $month->copy()->endOfMonth();
        $periodLabel = $from->format('F Y');

        return match ($type) {
            ReportType::ProfitLoss => self::profitLossReport($from, $to, $periodLabel),
            ReportType::Sales => self::salesReport($from, $to, $periodLabel),
            ReportType::Ingredients => self::ingredientsReport($from, $to, $periodLabel),
            ReportType::Inventory => self::inventoryReport($periodLabel),
            ReportType::Waste => self::wasteReport($from, $to, $periodLabel),
            ReportType::Orders => self::ordersReport($from, $to, $periodLabel),
            ReportType::Customers => self::customersReport($from, $to, $periodLabel),
            ReportType::Categories => self::categoriesReport($from, $to, $periodLabel),
            ReportType::Production => self::productionReport($from, $to, $periodLabel),
            ReportType::Shifts => self::shiftsReport($from, $to, $periodLabel),
        };
    }

    /**
     * @return list<array{
     *     type: ReportType,
     *     label: string,
     *     description: string,
     *     icon: string,
     *     href: string
     * }>
     */
    public static function catalogCards(): array
    {
        return array_map(fn (ReportType $type): array => [
            'type' => $type,
            'label' => $type->label(),
            'description' => $type->description(),
            'icon' => $type->icon(),
            'href' => route('admin.reports.show', $type),
        ], ReportType::catalog());
    }

    public static function cakesSoldBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) OrderItem::query()
            ->whereHas('order', function ($query) use ($from, $to): void {
                $query->where('status', '!=', OrderStatus::Cancelled)
                    ->whereBetween('created_at', [$from, $to]);
            })
            ->sum('quantity');
    }

    public static function paidEarningsBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) Order::query()
            ->where('status', '!=', OrderStatus::Cancelled)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('payment_status', [
                PaymentStatus::Paid,
                PaymentStatus::PartiallyPaid,
            ])
            ->sum('payment_amount');
    }

    public static function cancelledOrderCountBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return Order::query()
            ->where('status', OrderStatus::Cancelled)
            ->whereBetween('created_at', [$from, $to])
            ->count();
    }

    /**
     * Ingredient quantities implied by recipe × sold cakes.
     *
     * @return list<array{name: string, unit: string, quantity: float, cost: int}>
     */
    public static function ingredientUsage(CarbonInterface $from, CarbonInterface $to): array
    {
        $items = OrderItem::query()
            ->with(['cake.recipes.items.ingredient'])
            ->whereHas('order', function ($query) use ($from, $to): void {
                $query->where('status', '!=', OrderStatus::Cancelled)
                    ->whereBetween('created_at', [$from, $to]);
            })
            ->whereNotNull('cake_id')
            ->get();

        /** @var array<int, array{name: string, unit: string, quantity: float, cost: int}> $usage */
        $usage = [];

        foreach ($items as $item) {
            if ($item->cake === null) {
                continue;
            }

            $recipe = $item->cake->relationLoaded('recipes')
                ? $item->cake->recipes->first()
                : $item->cake->recipes()->with('items.ingredient')->orderBy('id')->first();

            if ($recipe === null) {
                continue;
            }

            if (! $recipe->relationLoaded('items')) {
                $recipe->load('items.ingredient');
            }

            foreach ($recipe->items as $recipeItem) {
                $ingredient = $recipeItem->ingredient;

                if ($ingredient === null) {
                    continue;
                }

                $qty = ((float) $recipeItem->quantity) * $item->quantity;
                $cost = (int) round($qty * $ingredient->unit_cost);
                $id = $ingredient->id;

                if (! isset($usage[$id])) {
                    $usage[$id] = [
                        'name' => $ingredient->name,
                        'unit' => $ingredient->unit->value,
                        'quantity' => 0.0,
                        'cost' => 0,
                    ];
                }

                $usage[$id]['quantity'] += $qty;
                $usage[$id]['cost'] += $cost;
            }
        }

        return array_values(collect($usage)
            ->sortByDesc('cost')
            ->map(fn (array $row): array => [
                'name' => $row['name'],
                'unit' => $row['unit'],
                'quantity' => round($row['quantity'], 3),
                'cost' => $row['cost'],
            ])
            ->all());
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function profitLossReport(CarbonInterface $from, CarbonInterface $to, string $periodLabel): array
    {
        $overview = self::monthOverview($from);
        $sellers = BakeryAnalytics::sellers($from, $to, 5);

        $rows = [
            ['Gross revenue (orders)', Money::format($overview['revenue']), ''],
            ['Real earnings (payments received)', Money::format($overview['paid_earnings']), ''],
            ['Outstanding (unpaid / balance)', Money::format($overview['outstanding']), ''],
            ['Ingredient + labor COGS', Money::format($overview['cogs']), ''],
            ['Gross profit', Money::format($overview['gross_profit']), ''],
            ['Waste / losses', Money::format($overview['waste_cost']), ''],
            ['Net profit after waste', Money::format($overview['net_profit']), ''],
        ];

        foreach ($sellers['best'] as $seller) {
            $rows[] = [
                'Top seller: '.$seller['name'],
                (string) $seller['quantity_sold'].' sold',
                Money::format($seller['revenue']),
            ];
        }

        return self::pack(
            ReportType::ProfitLoss,
            $periodLabel,
            $from,
            $to,
            [
                ['label' => 'Orders', 'value' => (string) $overview['order_count']],
                ['label' => 'Cakes sold', 'value' => (string) $overview['cakes_sold']],
                ['label' => 'Net profit', 'value' => Money::format($overview['net_profit'])],
            ],
            ['Line', 'Amount / qty', 'Detail'],
            $rows,
            'COGS uses recipe ingredient cost plus shop labor overhead when a cake has a recipe.',
        );
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function salesReport(CarbonInterface $from, CarbonInterface $to, string $periodLabel): array
    {
        $full = OrderItem::query()
            ->toBase()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('orders.created_at', [$from, $to])
            ->select('order_items.name')
            ->selectRaw('sum(order_items.quantity) as quantity_sold')
            ->selectRaw('coalesce(sum(order_items.quantity * order_items.unit_price), 0) as revenue')
            ->groupBy('order_items.name')
            ->orderByDesc('quantity_sold')
            ->get();

        $totalQty = (int) $full->sum(fn (stdClass $row): int => (int) $row->quantity_sold);
        $totalRevenue = (int) $full->sum(fn (stdClass $row): int => (int) $row->revenue);

        return self::pack(
            ReportType::Sales,
            $periodLabel,
            $from,
            $to,
            [
                ['label' => 'Cakes sold', 'value' => (string) $totalQty],
                ['label' => 'Revenue', 'value' => Money::format($totalRevenue)],
                ['label' => 'Products', 'value' => (string) $full->count()],
            ],
            ['Cake', 'Qty sold', 'Revenue'],
            array_values($full->map(fn (stdClass $row): array => [
                (string) $row->name,
                (string) (int) $row->quantity_sold,
                Money::format((int) $row->revenue),
            ])->all()),
            null,
        );
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function ingredientsReport(CarbonInterface $from, CarbonInterface $to, string $periodLabel): array
    {
        $usage = self::ingredientUsage($from, $to);
        $totalCost = (int) collect($usage)->sum('cost');

        return self::pack(
            ReportType::Ingredients,
            $periodLabel,
            $from,
            $to,
            [
                ['label' => 'Ingredients used', 'value' => (string) count($usage)],
                ['label' => 'Ingredient cost', 'value' => Money::format($totalCost)],
                ['label' => 'Cakes sold', 'value' => (string) self::cakesSoldBetween($from, $to)],
            ],
            ['Ingredient', 'Quantity', 'Cost'],
            array_map(fn (array $row): array => [
                $row['name'],
                rtrim(rtrim(number_format($row['quantity'], 3, '.', ''), '0'), '.').' '.$row['unit'],
                Money::format($row['cost']),
            ], $usage),
            'Usage is estimated from active recipes for cakes sold in the period.',
        );
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function inventoryReport(string $periodLabel): array
    {
        $ingredients = Ingredient::query()->orderBy('name')->get();
        $stockValue = (int) $ingredients->sum(
            fn (Ingredient $ingredient): int => (int) round(((float) $ingredient->current_stock) * $ingredient->unit_cost),
        );
        $lowStock = $ingredients->filter(fn (Ingredient $ingredient): bool => $ingredient->isLowStock())->count();

        return self::pack(
            ReportType::Inventory,
            $periodLabel,
            now()->startOfMonth(),
            now()->endOfMonth(),
            [
                ['label' => 'SKUs', 'value' => (string) $ingredients->count()],
                ['label' => 'Stock value', 'value' => Money::format($stockValue)],
                ['label' => 'Low stock', 'value' => (string) $lowStock],
            ],
            ['Ingredient', 'Stock', 'Unit cost', 'Value', 'Status'],
            array_values($ingredients->map(fn (Ingredient $ingredient): array => [
                $ingredient->name,
                $ingredient->stockLabel(),
                $ingredient->formattedUnitCost(),
                Money::format((int) round(((float) $ingredient->current_stock) * $ingredient->unit_cost)),
                $ingredient->isLowStock() ? 'Low stock' : ($ingredient->isExpiringSoon() ? 'Expiring soon' : 'OK'),
            ])->all()),
            'Snapshot of current inventory — not limited to the selected month.',
        );
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function wasteReport(CarbonInterface $from, CarbonInterface $to, string $periodLabel): array
    {
        $entries = WasteEntry::query()
            ->with(['ingredient', 'cake'])
            ->whereBetween('wasted_on', [$from->toDateString(), $to->toDateString()])
            ->latest('wasted_on')
            ->get();

        $total = (int) $entries->sum('cost_impact');

        return self::pack(
            ReportType::Waste,
            $periodLabel,
            $from,
            $to,
            [
                ['label' => 'Entries', 'value' => (string) $entries->count()],
                ['label' => 'Loss cost', 'value' => Money::format($total)],
            ],
            ['Date', 'Item', 'Qty', 'Reason', 'Cost'],
            array_values($entries->map(fn (WasteEntry $entry): array => [
                $entry->wasted_on->toFormattedDateString(),
                $entry->label(),
                (string) $entry->quantity,
                $entry->reason->label(),
                $entry->formattedCostImpact(),
            ])->all()),
            null,
        );
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function ordersReport(CarbonInterface $from, CarbonInterface $to, string $periodLabel): array
    {
        $orders = Order::query()
            ->with('user')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->get();

        $active = $orders->where('status', '!=', OrderStatus::Cancelled);
        $revenue = (int) $active->sum('subtotal');
        $paid = (int) $active->sum('payment_amount');

        return self::pack(
            ReportType::Orders,
            $periodLabel,
            $from,
            $to,
            [
                ['label' => 'Orders', 'value' => (string) $orders->count()],
                ['label' => 'Revenue', 'value' => Money::format($revenue)],
                ['label' => 'Collected', 'value' => Money::format($paid)],
            ],
            ['#', 'Date', 'Customer', 'Status', 'Payment', 'Total', 'Paid'],
            array_values($orders->map(fn (Order $order): array => [
                (string) $order->id,
                $order->created_at?->toDayDateTimeString() ?? '',
                $order->user->name,
                $order->status->label(),
                $order->payment_status->label(),
                Money::format($order->total_due > 0 ? $order->total_due : $order->subtotal),
                Money::format($order->payment_amount),
            ])->all()),
            null,
        );
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function customersReport(CarbonInterface $from, CarbonInterface $to, string $periodLabel): array
    {
        /** @var Collection<int, stdClass> $rows */
        $rows = Order::query()
            ->toBase()
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->where('orders.status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('orders.created_at', [$from, $to])
            ->select('users.name', 'users.email')
            ->selectRaw('count(orders.id) as order_count')
            ->selectRaw('coalesce(sum(orders.subtotal), 0) as spend')
            ->selectRaw('coalesce(sum(orders.payment_amount), 0) as paid')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('spend')
            ->limit(100)
            ->get();

        return self::pack(
            ReportType::Customers,
            $periodLabel,
            $from,
            $to,
            [
                ['label' => 'Customers', 'value' => (string) $rows->count()],
                ['label' => 'Spend', 'value' => Money::format((int) $rows->sum(fn (stdClass $row): int => (int) $row->spend))],
            ],
            ['Customer', 'Email', 'Orders', 'Spend', 'Paid'],
            array_values($rows->map(fn (stdClass $row): array => [
                (string) $row->name,
                (string) $row->email,
                (string) (int) $row->order_count,
                Money::format((int) $row->spend),
                Money::format((int) $row->paid),
            ])->all()),
            null,
        );
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function categoriesReport(CarbonInterface $from, CarbonInterface $to, string $periodLabel): array
    {
        $categories = BakeryAnalytics::revenueByCategory($from, $to);
        $total = (int) collect($categories)->sum('revenue');

        return self::pack(
            ReportType::Categories,
            $periodLabel,
            $from,
            $to,
            [
                ['label' => 'Categories', 'value' => (string) count($categories)],
                ['label' => 'Revenue', 'value' => Money::format($total)],
            ],
            ['Category', 'Revenue', 'Share'],
            array_map(fn (array $row): array => [
                $row['name'],
                Money::format($row['revenue']),
                $row['percent'].'%',
            ], $categories),
            null,
        );
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function productionReport(CarbonInterface $from, CarbonInterface $to, string $periodLabel): array
    {
        $counts = Order::query()
            ->toBase()
            ->select('production_status')
            ->selectRaw('count(*) as total')
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('production_status')
            ->pluck('total', 'production_status');

        $rows = [];
        $total = 0;

        foreach (ProductionStatus::cases() as $status) {
            $count = (int) $counts->get($status->value, 0);
            $total += $count;
            $rows[] = [$status->label(), (string) $count];
        }

        return self::pack(
            ReportType::Production,
            $periodLabel,
            $from,
            $to,
            [
                ['label' => 'Active orders', 'value' => (string) $total],
            ],
            ['Stage', 'Orders'],
            $rows,
            null,
        );
    }

    /**
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function shiftsReport(CarbonInterface $from, CarbonInterface $to, string $periodLabel): array
    {
        $shifts = Shift::query()
            ->with('user')
            ->whereBetween('starts_at', [$from, $to])
            ->orderBy('starts_at')
            ->get();

        $staffCount = User::query()->whereIn('role', [
            UserRole::Baker,
            UserRole::Decorator,
            UserRole::Cashier,
            UserRole::Manager,
            UserRole::Admin,
        ])->count();

        return self::pack(
            ReportType::Shifts,
            $periodLabel,
            $from,
            $to,
            [
                ['label' => 'Shifts', 'value' => (string) $shifts->count()],
                ['label' => 'Staff', 'value' => (string) $staffCount],
            ],
            ['Staff', 'Starts', 'Ends', 'Status'],
            array_values($shifts->map(fn (Shift $shift): array => [
                $shift->user->name,
                $shift->starts_at->toDayDateTimeString(),
                $shift->ends_at->toDayDateTimeString(),
                $shift->status->label(),
            ])->all()),
            null,
        );
    }

    /**
     * @param  list<array{label: string, value: string}>  $summary
     * @param  list<string>  $columns
     * @param  list<list<string>>  $rows
     * @return array{
     *     type: ReportType,
     *     title: string,
     *     description: string,
     *     period_label: string,
     *     from: CarbonInterface,
     *     to: CarbonInterface,
     *     summary: list<array{label: string, value: string}>,
     *     columns: list<string>,
     *     rows: list<list<string>>,
     *     footnote: string|null
     * }
     */
    private static function pack(
        ReportType $type,
        string $periodLabel,
        CarbonInterface $from,
        CarbonInterface $to,
        array $summary,
        array $columns,
        array $rows,
        ?string $footnote,
    ): array {
        return [
            'type' => $type,
            'title' => $type->label(),
            'description' => $type->description(),
            'period_label' => $periodLabel,
            'from' => $from,
            'to' => $to,
            'summary' => $summary,
            'columns' => $columns,
            'rows' => $rows,
            'footnote' => $footnote,
        ];
    }
}
