<div>
    <p class="font-script text-3xl text-primary">Designer</p>
    <h1 class="mt-1 text-4xl">Rules and options</h1>
    <p class="mt-2 max-w-2xl text-sm text-muted-foreground">Control how many tiers customers can pick, which looks are required, and what extras cost. Customers only tap cards; they never type a prompt.</p>
    <x-flash />

    <form wire:submit="saveSettings" class="mt-8 grid gap-4 rounded-4xl bg-card p-6 shadow-soft sm:grid-cols-2">
        <h2 class="sm:col-span-2 text-2xl">Cake rules</h2>
        <div>
            <label class="mb-1 block text-sm font-semibold">Minimum tiers</label>
            <input type="number" wire:model="min_tiers" min="1" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            @error('min_tiers') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Maximum tiers</label>
            <input type="number" wire:model="max_tiers" min="1" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
            @error('max_tiers') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Lead days</label>
            <input type="number" wire:model="lead_days" min="0" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold">Base price (Rs.)</label>
            <input type="number" wire:model="base_price_rupees" min="0" class="w-full rounded-2xl border border-input px-4 py-3 text-sm">
        </div>
        <div class="sm:col-span-2">
            <label class="mb-1 block text-sm font-semibold">Notice shown to customers</label>
            <textarea wire:model="notice" rows="2" class="w-full rounded-2xl border border-input px-4 py-3 text-sm"></textarea>
        </div>
        <button type="submit" class="rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground">Save rules</button>
    </form>

    <form wire:submit="addGroup" class="mt-8 space-y-3 rounded-4xl bg-card p-6 shadow-soft">
        <h2 class="text-2xl">Add option group</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <input type="text" wire:model="group_name" placeholder="Group name (e.g. Flavour)" class="rounded-2xl border border-input px-4 py-3 text-sm">
            <select wire:model="group_selection_type" class="rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                <option value="single">Single choice</option>
                <option value="multiple">Multiple choice</option>
            </select>
            <input type="number" wire:model="group_min_select" placeholder="Min select" class="rounded-2xl border border-input px-4 py-3 text-sm">
            <input type="number" wire:model="group_max_select" placeholder="Max select" class="rounded-2xl border border-input px-4 py-3 text-sm">
            <input type="number" wire:model="group_sort" placeholder="Sort" class="rounded-2xl border border-input px-4 py-3 text-sm">
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="group_is_required"> Required</label>
        </div>
        @error('group_name') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
        <button type="submit" class="rounded-full bg-secondary px-5 py-2.5 text-sm font-bold text-secondary-foreground">Add group</button>
    </form>

    <form wire:submit="addOption" class="mt-6 space-y-3 rounded-4xl bg-card p-6 shadow-soft">
        <h2 class="text-2xl">Add option</h2>
        <div class="grid gap-3 sm:grid-cols-2">
            <select wire:model="option_group_id" class="rounded-2xl border border-input bg-background px-4 py-3 text-sm">
                <option value="">Choose group</option>
                @foreach ($groups as $group)
                    <option value="{{ $group->id }}">{{ $group->name }}</option>
                @endforeach
            </select>
            <input type="text" wire:model="option_name" placeholder="Option name" class="rounded-2xl border border-input px-4 py-3 text-sm">
            <input type="text" wire:model="option_description" placeholder="Short description" class="rounded-2xl border border-input px-4 py-3 text-sm">
            <input type="color" wire:model="option_color" class="h-12 w-full rounded-2xl border border-input">
            <input type="number" wire:model="option_extra_rupees" placeholder="Extra Rs." class="rounded-2xl border border-input px-4 py-3 text-sm">
        </div>
        @error('option_name') <p class="text-sm text-destructive">{{ $message }}</p> @enderror
        <button type="submit" class="rounded-full bg-secondary px-5 py-2.5 text-sm font-bold text-secondary-foreground">Add option</button>
    </form>

    <div class="mt-8 space-y-4">
        @foreach ($groups as $group)
            <section wire:key="ag-{{ $group->id }}" class="rounded-4xl bg-card p-6 shadow-soft">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-xl">{{ $group->name }}</h2>
                        <p class="text-xs text-muted-foreground">
                            {{ $group->is_required ? 'Required' : 'Optional' }} · {{ $group->selection_type->value }} · min {{ $group->min_select }} / max {{ $group->max_select }}
                            · {{ $group->is_active ? 'Active' : 'Hidden' }}
                        </p>
                    </div>
                    <div>
                        <button type="button" wire:click="toggleGroup({{ $group->id }})" class="text-xs font-bold uppercase">Toggle</button>
                        <button type="button" wire:click="deleteGroup({{ $group->id }})" class="ml-3 text-xs font-bold uppercase text-destructive">Delete</button>
                    </div>
                </div>
                <ul class="mt-4 divide-y divide-border">
                    @foreach ($group->options as $option)
                        <li wire:key="ao-{{ $option->id }}" class="flex items-center justify-between gap-3 py-2 text-sm">
                            <span class="flex min-w-0 items-center gap-2">
                                @if ($option->illustrationUrl())
                                    <img src="{{ $option->illustrationUrl() }}" alt="" class="size-9 rounded-xl bg-muted object-contain">
                                @else
                                    <span class="inline-block size-3 shrink-0 rounded-full" style="background: {{ $option->color_hex ?? '#ccc' }}"></span>
                                @endif
                                <span class="truncate">
                                    {{ $option->name }}
                                    @if ($option->extra_price > 0)
                                        <span class="text-muted-foreground">(+ {{ $option->formattedExtraPrice() }})</span>
                                    @endif
                                    @unless ($option->is_active)
                                        <span class="text-xs uppercase text-muted-foreground">hidden</span>
                                    @endunless
                                </span>
                            </span>
                            <span>
                                <button type="button" wire:click="toggleOption({{ $option->id }})" class="text-xs font-bold uppercase">Toggle</button>
                                <button type="button" wire:click="deleteOption({{ $option->id }})" class="ml-3 text-xs font-bold uppercase text-destructive">Delete</button>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>
</div>
