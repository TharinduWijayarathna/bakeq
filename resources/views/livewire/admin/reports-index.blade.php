<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Insights</p>
            <h1 class="mt-1 text-3xl">Reports</h1>
            <p class="mt-2 max-w-xl text-sm text-muted-foreground">
                Month overview of sales, ingredient cost, earnings, and losses — then open any report for a PDF download.
            </p>
        </div>
        <div>
            <label class="mb-1 block text-[10px] font-bold uppercase tracking-wider text-muted-foreground">Month</label>
            <input
                type="month"
                wire:model.live="month"
                class="rounded-md border border-input bg-background px-3 py-2 text-sm"
            >
        </div>
    </div>

    <div class="mt-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <x-admin.stat
            label="Cakes sold"
            :value="$overview['cakes_sold']"
            :hint="$overview['order_count'].' orders · '.$overview['cancelled_orders'].' cancelled'"
            icon="cake"
        />
        <x-admin.stat
            label="Ingredients used"
            :value="$overview['ingredient_kinds']"
            :hint="'Recipe cost '.$overview['ingredient_cost_formatted']"
            icon="layers"
        />
        <x-admin.stat
            label="Ingredient cost"
            :value="$overview['ingredient_cost_formatted']"
            :hint="'COGS with labor '.$overview['cogs_formatted']"
            icon="package"
        />
        <x-admin.stat
            label="Gross revenue"
            :value="$overview['revenue_formatted']"
            :hint="'Outstanding '.$overview['outstanding_formatted']"
            icon="banknote"
        />
        <x-admin.stat
            label="Real earnings"
            :value="$overview['paid_earnings_formatted']"
            hint="Payments collected this month"
            icon="check"
        />
        <x-admin.stat
            label="Losses (waste)"
            :value="$overview['waste_cost_formatted']"
            :hint="'Net profit '.$overview['net_profit_formatted']"
            icon="trash"
        />
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-admin.panel class="p-4">
            <p class="text-xs text-muted-foreground">Gross profit</p>
            <p class="mt-1 text-lg font-bold tabular-nums">{{ $overview['gross_profit_formatted'] }}</p>
            <p class="mt-1 text-xs text-muted-foreground">Revenue − COGS</p>
        </x-admin.panel>
        <x-admin.panel class="p-4">
            <p class="text-xs text-muted-foreground">Net profit</p>
            <p class="mt-1 text-lg font-bold tabular-nums">{{ $overview['net_profit_formatted'] }}</p>
            <p class="mt-1 text-xs text-muted-foreground">After waste losses</p>
        </x-admin.panel>
        <x-admin.panel class="p-4">
            <p class="text-xs text-muted-foreground">Period</p>
            <p class="mt-1 text-lg font-bold">{{ $overview['month_label'] }}</p>
            <p class="mt-1 text-xs text-muted-foreground">Selected month</p>
        </x-admin.panel>
        <x-admin.panel class="p-4">
            <p class="text-xs text-muted-foreground">PDF reports</p>
            <p class="mt-1 text-lg font-bold">{{ count($reports) }}</p>
            <p class="mt-1 text-xs text-muted-foreground">Ready to download below</p>
        </x-admin.panel>
    </div>

    <div class="mt-10">
        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">All reports</p>
        <h2 class="mt-1 text-2xl">Open a report</h2>
        <p class="mt-1 text-sm text-muted-foreground">Each card opens a preview with a one-click PDF download.</p>

        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($reports as $report)
                <a
                    wire:key="report-{{ $report['type']->value }}"
                    href="{{ route('admin.reports.show', ['report' => $report['type']->value, 'month' => $month]) }}"
                    wire:navigate
                    class="group flex flex-col rounded-xl border border-border bg-card p-5 shadow-soft transition hover:border-primary/40 hover:bg-muted/40"
                >
                    <div class="flex items-start justify-between gap-3">
                        <span class="inline-flex size-10 items-center justify-center rounded-md bg-primary/10 text-primary">
                            <x-icon :name="$report['icon']" class="size-5" />
                        </span>
                        <x-icon name="arrow-right" class="size-4 text-muted-foreground transition group-hover:translate-x-0.5 group-hover:text-primary" />
                    </div>
                    <h3 class="mt-4 text-lg font-bold">{{ $report['label'] }}</h3>
                    <p class="mt-2 flex-1 text-sm text-muted-foreground">{{ $report['description'] }}</p>
                    <p class="mt-4 text-xs font-bold uppercase tracking-wider text-primary">View & download PDF</p>
                </a>
            @endforeach
        </div>
    </div>
</div>
