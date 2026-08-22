<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Overview</p>
            <h1 class="mt-1 text-3xl">Bakery dashboard</h1>
        </div>
        <p class="text-sm text-muted-foreground">{{ now()->toFormattedDateString() }}</p>
    </div>

    <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.stat label="Revenue this month" :value="$monthRevenue" :hint="$monthOrders.' orders this month'" icon="banknote" />
        <x-admin.stat label="Pending orders" :value="$pendingOrders" :hint="$todayOrders.' placed today'" icon="package" />
        <x-admin.stat label="Customers" :value="$customerCount" hint="Registered shoppers" icon="users" />
        <x-admin.stat label="Cakes" :value="$cakeCount" hint="On the menu" icon="cake" />
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
