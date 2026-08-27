<div>
    <p class="font-script text-3xl text-primary">Kitchen</p>
    <h1 class="mt-1 text-4xl">Orders</h1>
    <x-flash />

    <div class="mt-6 flex flex-wrap gap-2">
        <button type="button" wire:click="setTab('online')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $tab === 'online' ? 'bg-primary text-primary-foreground' : 'bg-card' }}">Online</button>
        <button type="button" wire:click="setTab('manual')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $tab === 'manual' ? 'bg-primary text-primary-foreground' : 'bg-card' }}">Manual</button>
        @if ($canUseAssistant)
            <button type="button" wire:click="setTab('ai')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $tab === 'ai' ? 'bg-primary text-primary-foreground' : 'bg-card' }}">
                <span class="inline-flex items-center gap-1.5"><x-icon name="sparkle" class="size-3.5" /> Order AI</span>
            </button>
        @endif
    </div>

    @if ($tab !== 'ai')
        <div class="mt-4 flex flex-wrap gap-2">
            <button type="button" wire:click="$set('status', '')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $status === '' ? 'bg-secondary text-secondary-foreground' : 'bg-card' }}">All statuses</button>
            @foreach ($statuses as $item)
                <button type="button" wire:key="st-{{ $item->value }}" wire:click="$set('status', '{{ $item->value }}')" class="rounded-full px-4 py-2 text-sm font-semibold {{ $status === $item->value ? 'bg-secondary text-secondary-foreground' : 'bg-card' }}">
                    {{ $item->label() }}
                </button>
            @endforeach
        </div>
    @endif

    @if ($tab === 'manual')
        <form wire:submit="createWalkIn" class="mt-8 grid gap-4 rounded-4xl bg-card p-6 shadow-soft lg:grid-cols-2">
            <h2 class="text-xl font-bold lg:col-span-2">Create walk-in order</h2>
            <div>
                <label class="mb-1 block text-sm font-semibold">Customer</label>
                <select wire:model="user_id" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                    <option value="">Choose customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->email }})</option>
                    @endforeach
                </select>
                @error('user_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Cake</label>
                <select wire:model="cake_id" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                    <option value="">Choose cake</option>
                    @foreach ($cakes as $cake)
                        <option value="{{ $cake->id }}">{{ $cake->name }} — {{ $cake->formattedPrice() }}</option>
                    @endforeach
                </select>
                @error('cake_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Quantity</label>
                <input type="number" wire:model="quantity" min="1" max="50" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('quantity') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Fulfillment</label>
                <select wire:model="fulfillment_method" class="w-full rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                    @foreach ($fulfillmentMethods as $method)
                        <option value="{{ $method->value }}">{{ $method->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Date</label>
                <input type="date" wire:model="delivery_date" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('delivery_date') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold">Address / pickup note</label>
                <input type="text" wire:model="delivery_address" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
                @error('delivery_address') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
            </div>
            <div class="lg:col-span-2">
                <label class="mb-1 block text-sm font-semibold">Notes</label>
                <textarea wire:model="notes" rows="2" class="w-full rounded-2xl border border-input px-4 py-3 text-sm"></textarea>
            </div>
            <div class="lg:col-span-2">
                <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Create walk-in order</button>
            </div>
        </form>
    @endif

    @if ($tab === 'ai' && $canUseAssistant)
        <p class="mt-6 max-w-2xl text-sm text-muted-foreground">
            Paste a WhatsApp, SMS, or email message. AI extracts the cake details into an editable form, then you send them to POS.
        </p>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            <section class="rounded-4xl bg-card p-6 shadow-soft">
                <h2 class="text-lg font-bold">Customer message</h2>
                <textarea
                    wire:model="ai_message"
                    rows="12"
                    placeholder="Hi, need a chocolate birthday cake for 15 people this Saturday afternoon, budget around 8000, with pink flowers please…"
                    class="mt-4 w-full rounded-3xl border border-input bg-background px-4 py-3 text-sm leading-relaxed"
                ></textarea>
                @error('ai_message') <p class="mt-2 text-sm text-destructive">{{ $message }}</p> @enderror

                <button
                    type="button"
                    wire:click="extract"
                    wire:loading.attr="disabled"
                    wire:target="extract"
                    class="mt-4 inline-flex items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="extract" class="inline-flex items-center gap-2">
                        <x-icon name="sparkle" class="size-4" /> Extract with AI
                    </span>
                    <span wire:loading.flex wire:target="extract" class="items-center gap-2">
                        <x-spinner /> Reading…
                    </span>
                </button>
            </section>

            <section class="rounded-4xl bg-card p-6 shadow-soft">
                <h2 class="text-lg font-bold">Extracted details</h2>
                @if (! $ai_extracted)
                    <p class="mt-4 text-sm text-muted-foreground">Results appear here after extraction. You can always edit before sending to POS.</p>
                @else
                    @if ($ai_failed)
                        <p class="mt-3 rounded-2xl bg-destructive/10 px-3 py-2 text-sm text-destructive">AI unavailable — fill in manually and continue.</p>
                    @endif

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Line name</label>
                            <input type="text" wire:model="line_name" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                            @error('line_name') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Match customer</label>
                            <select wire:model="assistant_user_id" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                                <option value="">None / choose later</option>
                                @foreach ($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Occasion</label>
                            <input type="text" wire:model="occasion" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Flavour</label>
                            <input type="text" wire:model="flavor" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Servings</label>
                            <input type="text" wire:model="servings" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Budget (Rs)</label>
                            <input type="text" wire:model="budget" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Date</label>
                            <input type="text" wire:model="ai_date" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Time</label>
                            <input type="text" wire:model="time" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Customer name</label>
                            <input type="text" wire:model="customer_name" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-bold uppercase">Phone</label>
                            <input type="text" wire:model="phone" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-bold uppercase">Style / notes</label>
                            <textarea wire:model="style_notes" rows="4" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm"></textarea>
                        </div>
                    </div>

                    <button
                        type="button"
                        wire:click="sendToPos"
                        class="mt-5 inline-flex items-center justify-center gap-2 rounded-full bg-secondary px-6 py-3 text-sm font-bold text-secondary-foreground"
                    >
                        <x-icon name="banknote" class="size-4" /> Send to POS
                    </button>
                @endif
            </section>
        </div>
    @endif

    @if ($tab !== 'ai')
        <div class="mt-6 overflow-x-auto rounded-4xl bg-card shadow-soft">
            <table class="w-full text-left text-sm">
                <thead class="bg-muted text-xs uppercase tracking-wider text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Origin</th>
                        <th class="px-4 py-3">Total due</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr wire:key="ao-{{ $order->id }}" class="border-t border-border">
                            <td class="px-4 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-primary" wire:navigate>#{{ $order->id }}</a></td>
                            <td class="px-4 py-3">{{ $order->user->name }}</td>
                            <td class="px-4 py-3">{{ $order->delivery_date->toFormattedDateString() }}</td>
                            <td class="px-4 py-3">{{ $order->status->label() }}</td>
                            <td class="px-4 py-3">
                                @if ($order->origin->isAiDesigned())
                                    <span class="inline-flex rounded-full bg-primary/10 px-2.5 py-1 text-xs font-bold text-primary">AI Designed</span>
                                @else
                                    <span class="text-muted-foreground">Catalog</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $order->formattedTotalDue() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-6 text-muted-foreground">No {{ $tab }} orders.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
