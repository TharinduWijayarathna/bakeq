<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Overview</p>
            <h1 class="mt-1 text-3xl">Bakery dashboard</h1>
        </div>
        <p class="text-sm text-muted-foreground">{{ now()->toFormattedDateString() }}</p>
    </div>

    <x-flash />

    <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat label="Revenue this month" :value="$monthRevenue" :hint="$monthOrders.' orders this month'" icon="banknote" />
        <x-admin.stat label="Pending orders" :value="$pendingOrders" :hint="$todayOrders.' placed today'" icon="package" />
        <x-admin.stat label="Customers" :value="$customerCount" hint="Registered shoppers" icon="users" />
        <x-admin.stat label="Cakes" :value="$cakeCount" hint="On the menu" icon="cake" />
    </div>

    <div class="mt-4 grid gap-3 xl:grid-cols-4">
        <x-admin.panel class="p-5 xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Intelligence</p>
                    <h2 class="mt-1 text-xl">This month vs budget</h2>
                </div>
                <form wire:submit="saveBudget" class="flex items-end gap-2">
                    <div>
                        <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Budget Rs.</label>
                        <input type="number" min="0" step="1" wire:model="monthly_budget_rupees" class="w-28 rounded-md border border-input bg-background px-2 py-1.5 text-sm">
                    </div>
                    <button type="submit" class="rounded-md bg-secondary px-3 py-1.5 text-xs font-bold uppercase text-secondary-foreground">Save</button>
                </form>
            </div>
            @error('monthly_budget_rupees') <p class="mt-2 text-sm text-destructive">{{ $message }}</p> @enderror

            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-md bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground">Revenue</p>
                    <p class="mt-1 text-lg font-bold tabular-nums">{{ $monthSummary['revenue_formatted'] }}</p>
                    @if ($monthSummary['revenue_change_percent'] !== null)
                        <p class="mt-1 text-xs {{ $monthSummary['revenue_change_percent'] >= 0 ? 'text-primary' : 'text-destructive' }}">
                            {{ $monthSummary['revenue_change_percent'] >= 0 ? '+' : '' }}{{ $monthSummary['revenue_change_percent'] }}% vs last month ({{ $monthSummary['previous_revenue_formatted'] }})
                        </p>
                    @else
                        <p class="mt-1 text-xs text-muted-foreground">No prior month to compare</p>
                    @endif
                </div>
                <div class="rounded-md bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground">Budget progress</p>
                    <p class="mt-1 text-lg font-bold tabular-nums">
                        {{ $monthSummary['budget_progress_percent'] !== null ? $monthSummary['budget_progress_percent'].'%' : '—' }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">Target {{ $monthSummary['budget_formatted'] }}</p>
                    @if ($monthSummary['budget'] > 0)
                        <div class="mt-2 h-1.5 overflow-hidden rounded-[3px] bg-muted">
                            <div class="h-full bg-primary" style="width: {{ min(100, $monthSummary['budget_progress_percent'] ?? 0) }}%"></div>
                        </div>
                    @endif
                </div>
                <div class="rounded-md bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground">Gross profit</p>
                    <p class="mt-1 text-lg font-bold tabular-nums">{{ $monthSummary['gross_profit_formatted'] }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">COGS {{ $monthSummary['cogs_formatted'] }} · margin {{ $monthSummary['margin_percent'] }}%</p>
                </div>
                <div class="rounded-md bg-muted/50 p-3">
                    <p class="text-xs text-muted-foreground">Net profit after waste</p>
                    <p class="mt-1 text-lg font-bold tabular-nums">{{ $monthSummary['net_profit_formatted'] }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">Waste {{ $monthSummary['waste_cost_formatted'] }}</p>
                </div>
            </div>
        </x-admin.panel>

        <x-admin.panel class="p-5 xl:col-span-2">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Margin</p>
            <h2 class="mt-1 text-xl">Gross margin · last 8 weeks</h2>
            @if ($marginTrendChart['hasData'])
                <svg class="mt-4 h-40 w-full" viewBox="0 0 {{ $marginTrendChart['width'] }} {{ $marginTrendChart['height'] }}" role="img" aria-label="Margin trend">
                    @foreach ($marginTrendChart['yLabels'] as $label)
                        <line x1="{{ $marginTrendChart['plotLeft'] }}" y1="{{ $label['y'] }}" x2="{{ $marginTrendChart['width'] - 16 }}" y2="{{ $label['y'] }}" stroke="currentColor" class="text-border" stroke-width="1" />
                        <text x="0" y="{{ $label['y'] + 4 }}" class="fill-muted-foreground text-[11px]">{{ $label['text'] }}</text>
                    @endforeach
                    <polyline points="{{ $marginTrendChart['polyline'] }}" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                    @foreach ($marginTrendChart['points'] as $point)
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3" fill="var(--card)" stroke="var(--primary)" stroke-width="2">
                            <title>{{ $point['label'] }} · {{ $point['margin'] }}</title>
                        </circle>
                    @endforeach
                    @foreach ($marginTrendChart['xLabels'] as $label)
                        <text x="{{ $label['x'] }}" y="{{ $marginTrendChart['height'] - 6 }}" text-anchor="middle" class="fill-muted-foreground text-[10px]">{{ $label['text'] }}</text>
                    @endforeach
                </svg>
            @else
                <p class="mt-8 text-sm text-muted-foreground">Add recipes and sales to see margin trend.</p>
            @endif
            <p class="mt-3 text-xs text-muted-foreground">COGS uses recipe ingredient + labor overhead when a cake has a recipe.</p>
        </x-admin.panel>
    </div>

    <div class="mt-4 grid gap-3 xl:grid-cols-3">
        <x-admin.panel class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Sellers</p>
            <h2 class="mt-1 text-xl">Best this month</h2>
            <ul class="mt-4 space-y-2">
                @forelse ($sellers['best'] as $seller)
                    <li wire:key="best-{{ $seller['name'] }}" class="flex items-center justify-between gap-3 text-sm">
                        <span class="truncate font-semibold">{{ $seller['name'] }}</span>
                        <span class="shrink-0 tabular-nums text-muted-foreground">{{ $seller['quantity_sold'] }}</span>
                    </li>
                @empty
                    <li class="text-sm text-muted-foreground">No sales yet this month.</li>
                @endforelse
            </ul>
            <h3 class="mt-5 text-sm font-bold uppercase tracking-wider text-muted-foreground">Slowest</h3>
            <ul class="mt-3 space-y-2">
                @forelse ($sellers['worst'] as $seller)
                    <li wire:key="worst-{{ $seller['name'] }}" class="flex items-center justify-between gap-3 text-sm">
                        <span class="truncate">{{ $seller['name'] }}</span>
                        <span class="shrink-0 tabular-nums text-muted-foreground">{{ $seller['quantity_sold'] }}</span>
                    </li>
                @empty
                    <li class="text-sm text-muted-foreground">—</li>
                @endforelse
            </ul>
        </x-admin.panel>

        <x-admin.panel class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Mix</p>
            <h2 class="mt-1 text-xl">Revenue by category</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($categories as $category)
                    <li wire:key="cat-{{ $category['name'] }}" class="space-y-1.5">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="truncate font-semibold">{{ $category['name'] }}</span>
                            <span class="shrink-0 tabular-nums text-muted-foreground">{{ $category['percent'] }}%</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-[3px] bg-muted">
                            <div class="h-full bg-primary" style="width: {{ max(4, $category['percent']) }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-muted-foreground">No category revenue yet.</li>
                @endforelse
            </ul>
        </x-admin.panel>

        <x-admin.panel class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Procurement</p>
            <h2 class="mt-1 text-xl">Suggested reorders</h2>
            <p class="mt-2 text-sm text-muted-foreground">{{ $procurement['summary'] }}</p>
            <ul class="mt-4 space-y-3">
                @forelse ($procurement['items'] as $item)
                    <li wire:key="proc-{{ $item['ingredient_id'] }}" class="rounded-md bg-muted/40 p-3">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold">{{ $item['name'] }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ $item['reason'] }}</p>
                            </div>
                            <p class="shrink-0 text-sm font-bold tabular-nums">{{ \App\Support\Money::format($item['estimated_cost']) }}</p>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-muted-foreground">No reorders suggested right now.</li>
                @endforelse
            </ul>
            @if ($procurement['estimated_total_cost'] > 0)
                <p class="mt-4 text-sm font-semibold">Est. total {{ \App\Support\Money::format($procurement['estimated_total_cost']) }}</p>
            @endif
        </x-admin.panel>
    </div>

    <div class="mt-4 grid gap-3 xl:grid-cols-3">
        <x-admin.panel class="p-5 xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Forecast</p>
                    <h2 class="mt-1 text-xl">Demand · next {{ $forecast['horizon_weeks'] }} weeks</h2>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2.5 py-1 text-xs font-semibold text-secondary-foreground">
                    {{ $forecast['lookback_weeks'] }}-week moving average
                </span>
            </div>
            <p class="mt-3 text-sm leading-relaxed text-muted-foreground">{{ $forecast['summary'] }}</p>

            @if ($forecast['chart']['hasData'])
                <svg class="mt-4 h-48 w-full" viewBox="0 0 {{ $forecast['chart']['width'] }} {{ $forecast['chart']['height'] }}" role="img" aria-label="Order volume forecast">
                    @foreach ($forecast['chart']['yLabels'] as $label)
                        <line x1="{{ $forecast['chart']['plotLeft'] }}" y1="{{ $label['y'] }}" x2="{{ $forecast['chart']['width'] - 16 }}" y2="{{ $label['y'] }}" stroke="currentColor" class="text-border" stroke-width="1" />
                        <text x="4" y="{{ $label['y'] + 4 }}" class="fill-muted-foreground text-[11px]">{{ $label['text'] }}</text>
                    @endforeach
                    <polyline points="{{ $forecast['chart']['historyPolyline'] }}" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                    <polyline points="{{ $forecast['chart']['forecastPolyline'] }}" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-dasharray="6 4" stroke-linejoin="round" stroke-linecap="round" opacity="0.75" />
                    @foreach ($forecast['chart']['points'] as $point)
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.5" fill="var(--card)" stroke="var(--primary)" stroke-width="2">
                            <title>{{ $point['label'] }} · {{ $point['orders'] }} orders ({{ $point['kind'] }})</title>
                        </circle>
                    @endforeach
                    @foreach ($forecast['chart']['xLabels'] as $label)
                        <text x="{{ $label['x'] }}" y="{{ $forecast['chart']['height'] - 6 }}" text-anchor="middle" class="fill-muted-foreground text-[10px]">{{ $label['text'] }}</text>
                    @endforeach
                </svg>
                <p class="mt-2 text-xs text-muted-foreground">Solid = history · dashed = forecast (~{{ $forecast['average_weekly_orders'] }} orders/week)</p>
            @else
                <div class="mt-8 grid h-40 place-items-center rounded-md border border-dashed border-border bg-muted/40 text-sm text-muted-foreground">
                    Need a few weeks of orders before the forecast has something to average.
                </div>
            @endif
        </x-admin.panel>

        <x-admin.panel class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Flavours</p>
            <h2 class="mt-1 text-xl">Recent favourites</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($forecast['top_flavors'] as $flavor)
                    <li wire:key="flavor-{{ $flavor['name'] }}" class="flex items-center justify-between gap-3 text-sm">
                        <span class="truncate font-semibold">{{ $flavor['name'] }}</span>
                        <span class="shrink-0 tabular-nums text-muted-foreground">{{ $flavor['quantity'] }}</span>
                    </li>
                @empty
                    <li class="text-sm text-muted-foreground">No flavour history yet.</li>
                @endforelse
            </ul>
        </x-admin.panel>
    </div>

    <div class="mt-4 grid gap-3 xl:grid-cols-3">
        <x-admin.panel class="p-5 xl:col-span-2">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Analytics</p>
                    <h2 class="mt-1 text-xl">Revenue · last 14 days</h2>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-md bg-secondary px-2.5 py-1 text-xs font-semibold text-secondary-foreground">
                    <x-icon name="chart" class="size-3.5" /> Cancelled orders excluded
                </span>
            </div>

            @if ($revenueChart['hasData'])
                <svg class="mt-4 h-56 w-full" viewBox="0 0 {{ $revenueChart['width'] }} {{ $revenueChart['height'] }}" role="img" aria-label="Revenue for the last 14 days">
                    @foreach ($revenueChart['yLabels'] as $label)
                        <line x1="{{ $revenueChart['plotLeft'] }}" y1="{{ $label['y'] }}" x2="{{ $revenueChart['width'] - 16 }}" y2="{{ $label['y'] }}" stroke="currentColor" class="text-border" stroke-width="1" />
                        <text x="0" y="{{ $label['y'] + 4 }}" class="fill-muted-foreground text-[11px]">{{ $label['text'] }}</text>
                    @endforeach
                    <path d="{{ $revenueChart['area'] }}" fill="var(--primary)" fill-opacity="0.12" />
                    <polyline points="{{ $revenueChart['polyline'] }}" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linejoin="round" stroke-linecap="round" />
                    @foreach ($revenueChart['points'] as $point)
                        <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.5" fill="var(--card)" stroke="var(--primary)" stroke-width="2">
                            <title>{{ $point['label'] }} · {{ $point['revenue'] }} · {{ $point['orders'] }} orders</title>
                        </circle>
                    @endforeach
                    @foreach ($revenueChart['xLabels'] as $label)
                        <text x="{{ $label['x'] }}" y="{{ $revenueChart['height'] - 8 }}" text-anchor="middle" class="fill-muted-foreground text-[11px]">{{ $label['text'] }}</text>
                    @endforeach
                </svg>
            @else
                <div class="mt-8 grid h-48 place-items-center rounded-md border border-dashed border-border bg-muted/40 text-sm text-muted-foreground">
                    No order activity in the last 14 days yet.
                </div>
            @endif
        </x-admin.panel>

        <x-admin.panel class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Mix</p>
            <h2 class="mt-1 text-xl">Order status</h2>

            <div class="mt-5 flex items-center gap-5">
                <svg class="size-32 shrink-0" viewBox="0 0 100 100" role="img" aria-label="Orders by status">
                    <circle cx="50" cy="50" r="38" fill="none" stroke="currentColor" class="text-muted" stroke-width="12" />
                    @foreach ($statusBreakdown as $segment)
                        @if ($segment['count'] > 0)
                            <circle
                                cx="50"
                                cy="50"
                                r="38"
                                fill="none"
                                stroke="{{ $segment['color'] }}"
                                stroke-width="12"
                                stroke-dasharray="{{ $segment['dash'] }}"
                                stroke-dashoffset="{{ $segment['offset'] }}"
                                stroke-linecap="butt"
                                transform="rotate(-90 50 50)"
                            >
                                <title>{{ $segment['label'] }} · {{ $segment['count'] }}</title>
                            </circle>
                        @endif
                    @endforeach
                    <text x="50" y="47" text-anchor="middle" fill="currentColor" font-size="16" font-weight="700">{{ $totalOrders }}</text>
                    <text x="50" y="61" text-anchor="middle" fill="currentColor" font-size="8" class="text-muted-foreground">orders</text>
                </svg>
                <ul class="min-w-0 flex-1 space-y-2.5">
                    @foreach ($statusBreakdown as $segment)
                        <li wire:key="status-{{ $segment['label'] }}" class="flex items-center justify-between gap-3 text-sm">
                            <span class="flex min-w-0 items-center gap-2">
                                <span class="size-2.5 shrink-0 rounded-[2px]" style="background: {{ $segment['color'] }}"></span>
                                <span class="truncate">{{ $segment['label'] }}</span>
                            </span>
                            <span class="tabular-nums text-muted-foreground">{{ $segment['count'] }} · {{ $segment['percent'] }}%</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </x-admin.panel>
    </div>

    <div class="mt-4 grid gap-3 xl:grid-cols-3">
        <x-admin.panel class="p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Bestsellers</p>
            <h2 class="mt-1 text-xl">Top cakes</h2>
            <ul class="mt-4 space-y-3">
                @forelse ($topCakes as $cake)
                    <li wire:key="top-cake-{{ $cake['name'] }}" class="space-y-1.5">
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="truncate font-semibold">{{ $cake['name'] }}</span>
                            <span class="shrink-0 tabular-nums text-muted-foreground">{{ $cake['quantity_sold'] }} sold</span>
                        </div>
                        <div class="h-1.5 overflow-hidden rounded-[3px] bg-muted">
                            <div class="h-full bg-primary" style="width: {{ $cake['percent'] }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-muted-foreground">No cake sales yet.</li>
                @endforelse
            </ul>
        </x-admin.panel>

        <x-admin.panel class="overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between gap-3 px-5 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Kitchen</p>
                    <h2 class="text-xl">Recent orders</h2>
                </div>
                <a href="{{ route('admin.orders.index') }}" class="text-sm font-semibold text-primary">View all</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-y border-border bg-muted/70 text-xs uppercase tracking-wider text-muted-foreground">
                        <tr>
                            <th class="px-5 py-2.5 font-semibold">Order</th>
                            <th class="px-5 py-2.5 font-semibold">Customer</th>
                            <th class="px-5 py-2.5 font-semibold">Status</th>
                            <th class="px-5 py-2.5 font-semibold">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOrders as $order)
                            <tr wire:key="dash-order-{{ $order->id }}" class="border-t border-border">
                                <td class="px-5 py-3">
                                    <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-primary">#{{ $order->id }}</a>
                                </td>
                                <td class="px-5 py-3">{{ $order->user->name }}</td>
                                <td class="px-5 py-3">
                                    <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-semibold {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
                                </td>
                                <td class="px-5 py-3 tabular-nums">{{ $order->formattedSubtotal() }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-8 text-muted-foreground">No orders yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-admin.panel>
    </div>
</div>
