<div>
    <p class="font-script text-3xl text-primary">Bake floor</p>
    <h1 class="mt-1 text-4xl">Production</h1>
    <x-flash />
    @error('board') <p class="mt-4 rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive">{{ $message }}</p> @enderror

    <div class="mt-8 flex gap-4 overflow-x-auto pb-4">
        @foreach ($statuses as $status)
            <section wire:key="col-{{ $status->value }}" class="w-72 shrink-0 rounded-4xl bg-card p-4 shadow-soft">
                <h2 class="text-sm font-bold uppercase tracking-wider text-muted-foreground">{{ $status->label() }}</h2>
                <p class="mt-1 text-xs text-muted-foreground">{{ $columns[$status->value]->count() }} orders</p>
                <div class="mt-4 space-y-3">
                    @forelse ($columns[$status->value] as $order)
                        <article wire:key="prod-{{ $order->id }}" class="rounded-3xl border border-border bg-background p-3">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-bold text-primary">#{{ $order->id }}</a>
                            <p class="mt-1 text-sm">{{ $order->user->name }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ $order->delivery_date->toFormattedDateString() }}</p>
                            <p class="mt-2 text-sm font-semibold">{{ $order->formattedTotalDue() }}</p>
                            <label class="mt-3 block text-[10px] font-bold uppercase text-muted-foreground">Move to</label>
                            <select wire:change="move({{ $order->id }}, $event.target.value)" class="mt-1 w-full rounded-xl border border-input bg-card px-2 py-1.5 text-xs">
                                @foreach ($statuses as $option)
                                    <option value="{{ $option->value }}" @selected($option === $status)>{{ $option->label() }}</option>
                                @endforeach
                            </select>
                        </article>
                    @empty
                        <p class="text-xs text-muted-foreground">Empty</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
