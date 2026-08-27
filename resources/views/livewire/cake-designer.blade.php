<div class="pb-28 lg:pb-0">
    <section class="relative overflow-hidden bg-gradient-hero">
        <div class="absolute inset-0 dots-pattern opacity-20" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-6xl px-5 py-10 sm:py-14">
            <p class="font-script text-3xl text-primary-foreground/90 sm:text-4xl">Designer</p>
            <h1 class="mt-1 max-w-xl text-4xl leading-[0.95] text-[color:var(--hero-deep)] sm:text-5xl">
                {{ $mode === 'describe' ? 'Describe the cake you want' : 'Tap the look you want' }}
            </h1>
            <p class="mt-4 max-w-xl text-sm leading-relaxed text-[color:var(--hero-deep)]/80 sm:text-base">
                @if ($mode === 'describe')
                    Write a free-text description. We generate a preview you can add to cart with notes for the baker.
                @else
                    Choose cake type, tiers, flavour and finish from the cards, or switch to Describe it for free text.
                @endif
            </p>
            <div class="mt-5 flex flex-wrap gap-2">
                <button
                    type="button"
                    wire:click="setMode('studio')"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wider {{ $mode === 'studio' ? 'bg-[color:var(--hero-deep)] text-primary-foreground' : 'bg-card/80 text-[color:var(--hero-deep)]' }}"
                >
                    <x-icon name="layers" class="size-3.5" /> Studio
                </button>
                <button
                    type="button"
                    wire:click="setMode('describe')"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-xs font-bold uppercase tracking-wider {{ $mode === 'describe' ? 'bg-[color:var(--hero-deep)] text-primary-foreground' : 'bg-card/80 text-[color:var(--hero-deep)]' }}"
                >
                    <x-icon name="sparkle" class="size-3.5" /> Describe it
                </button>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-card/80 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-[color:var(--hero-deep)]">
                    <x-icon name="cake" class="size-3.5" /> {{ $settings->lead_days }} day lead time
                </span>
            </div>
            @if (filled($settings->notice))
                <p class="mt-4 max-w-xl text-xs text-[color:var(--hero-deep)]/70">{{ $settings->notice }}</p>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-6xl px-5 py-10">
        <x-flash />

        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1.15fr)_minmax(18rem,0.85fr)]">
            <div
                class="space-y-6 transition duration-300"
                wire:loading.class="pointer-events-none opacity-50"
                wire:target="generate,generateFromPrompt"
            >
                @if ($mode === 'describe')
                    <section class="rounded-4xl bg-card p-6 shadow-soft">
                        <h2 class="text-2xl">Your description</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Be specific about flavour, size, colour, and decorations.</p>
                        <textarea
                            wire:model="prompt"
                            rows="8"
                            placeholder="e.g. Two-tier vanilla cake with soft pink buttercream, fresh strawberries on top, for a 30th birthday, about 20 servings"
                            class="mt-5 w-full rounded-3xl border border-input bg-background px-4 py-3 text-sm leading-relaxed"
                        ></textarea>
                        @error('prompt') <p class="mt-2 text-sm text-destructive">{{ $message }}</p> @enderror

                        <label class="mt-5 mb-1 block text-sm font-semibold">Notes for the baker (optional)</label>
                        <textarea
                            wire:model="cartNotes"
                            rows="3"
                            placeholder="Allergy notes, delivery timing, writing on cake…"
                            class="w-full rounded-3xl border border-input bg-background px-4 py-3 text-sm"
                        ></textarea>
                        @error('cartNotes') <p class="mt-2 text-sm text-destructive">{{ $message }}</p> @enderror
                    </section>
                @else
                    <section class="rounded-4xl bg-card p-6 shadow-soft">
                        <div class="flex items-baseline justify-between gap-3">
                            <div>
                                <h2 class="text-2xl">Tiers</h2>
                                <p class="mt-1 text-sm text-muted-foreground">Choose between {{ $settings->min_tiers }} and {{ $settings->max_tiers }}.</p>
                            </div>
                            <x-icon name="layers" class="size-5 text-primary" />
                        </div>
                        <div class="mt-5 flex flex-wrap gap-2">
                            @foreach ($tierRange as $tier)
                                <button
                                    type="button"
                                    wire:click="$set('tiers', {{ $tier }})"
                                    wire:key="tier-{{ $tier }}"
                                    aria-pressed="{{ (int) $tiers === (int) $tier ? 'true' : 'false' }}"
                                    class="inline-flex min-w-16 flex-col items-center gap-0.5 rounded-3xl px-4 py-3 text-center transition {{ (int) $tiers === (int) $tier ? 'bg-primary text-primary-foreground shadow-soft' : 'bg-muted hover:bg-secondary' }}"
                                >
                                    <span class="font-display text-xl font-bold leading-none">{{ $tier }}</span>
                                    <span class="text-[10px] font-bold uppercase tracking-wider">{{ $tier === 1 ? 'tier' : 'tiers' }}</span>
                                </button>
                            @endforeach
                        </div>
                        @error('tiers') <p class="mt-3 text-sm text-destructive">{{ $message }}</p> @enderror
                    </section>

                    @foreach ($groups as $group)
                        <section wire:key="group-{{ $group->id }}" class="rounded-4xl bg-card p-6 shadow-soft">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h2 class="text-2xl">{{ $group->name }}</h2>
                                    <p class="mt-1 text-sm text-muted-foreground">
                                        @if ($group->selection_type->value === 'multiple')
                                            Pick up to {{ $group->max_select }}
                                        @else
                                            Pick one
                                        @endif
                                    </p>
                                </div>
                                <span class="shrink-0 rounded-full px-3 py-1 text-[10px] font-bold uppercase tracking-wider {{ $group->is_required ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground' }}">
                                    {{ $group->is_required ? 'Required' : 'Optional' }}
                                </span>
                            </div>
                            <div class="mt-5 grid grid-cols-2 gap-3 {{ $group->slug === 'cake-type' ? 'sm:grid-cols-3 lg:grid-cols-5' : 'sm:grid-cols-3' }}">
                                @foreach ($group->options as $option)
                                    @php
                                        $selected = $this->isSelected($group->id, $option->id);
                                    @endphp
                                    <button
                                        type="button"
                                        wire:click="selectOption({{ $group->id }}, {{ $option->id }})"
                                        wire:key="opt-{{ $option->id }}"
                                        aria-pressed="{{ $selected ? 'true' : 'false' }}"
                                        class="group relative rounded-3xl border-2 p-3 text-left transition {{ $selected ? 'border-primary bg-secondary shadow-soft' : 'border-transparent bg-muted/70 hover:border-primary/40 hover:bg-card' }}"
                                    >
                                        <x-designer.option-art :option="$option" :selected="$selected" class="mb-3" />
                                        <span class="block text-sm font-bold leading-snug">{{ $option->name }}</span>
                                        @if (filled($option->description))
                                            <span class="mt-1 block text-xs leading-snug text-muted-foreground">{{ $option->description }}</span>
                                        @endif
                                        @if ($option->extra_price > 0)
                                            <span class="mt-2 inline-flex rounded-full bg-card px-2 py-0.5 text-[11px] font-semibold text-primary">+ {{ $option->formattedExtraPrice() }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                            @error('selections.'.$group->id) <p class="mt-3 text-sm text-destructive">{{ $message }}</p> @enderror
                        </section>
                    @endforeach

                    <section class="rounded-4xl bg-card p-6 shadow-soft">
                        <label class="mb-1 block text-sm font-semibold">Notes for the baker (optional)</label>
                        <textarea
                            wire:model="cartNotes"
                            rows="3"
                            placeholder="Allergy notes, delivery timing, writing on cake…"
                            class="w-full rounded-3xl border border-input bg-background px-4 py-3 text-sm"
                        ></textarea>
                    </section>
                @endif
            </div>

            <aside class="lg:sticky lg:top-24">
                <div class="overflow-hidden rounded-4xl bg-card shadow-sweet">
                    <div class="flex items-start justify-between gap-3 px-6 pt-6">
                        <div>
                            <p class="font-script text-2xl leading-none text-primary">{{ $mode === 'describe' ? 'Describe' : 'Studio' }}</p>
                            <h2 class="mt-1 text-2xl">Preview</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                @if ($mode === 'describe')
                                    from {{ $settings->formattedBasePrice() }}
                                @else
                                    {{ $tiers }} {{ $tiers === 1 ? 'tier' : 'tiers' }} · from {{ $settings->formattedBasePrice() }}
                                @endif
                            </p>
                        </div>
                        <p class="font-display text-2xl font-bold text-primary">{{ $estimatedPrice }}</p>
                    </div>

                    <div class="relative mx-6 mt-5 mb-6 overflow-hidden rounded-3xl bg-secondary ring-1 ring-border/70 lg:mb-0">
                        @if ($design && filled($design->preview_path))
                            <img wire:key="preview-{{ $design->id }}-{{ $design->preview_path }}" src="{{ $design->previewUrl() }}" alt="Generated cake preview" class="h-80 w-full object-cover" wire:loading.class="opacity-40" wire:target="generate,generateFromPrompt">
                        @else
                            <div class="grid h-72 place-items-center px-6 text-center sm:h-80" wire:loading.class="opacity-0" wire:target="generate,generateFromPrompt">
                                <div>
                                    <span class="mx-auto mb-3 grid size-16 place-items-center rounded-3xl bg-card text-primary shadow-soft">
                                        <x-icon name="cake" class="size-7" />
                                    </span>
                                    <p class="text-sm font-semibold">Your cake will appear here</p>
                                    <p class="mt-1 text-xs text-muted-foreground">
                                        {{ $mode === 'describe' ? 'Describe it, then generate a preview.' : 'Select the cards, then generate a preview.' }}
                                    </p>
                                </div>
                            </div>
                        @endif
                        <x-designer.generating
                            :active="$generating"
                            :target="$mode === 'describe' ? 'generateFromPrompt' : 'generate'"
                            wire:key="generating-{{ $generating ? 'active' : 'loading' }}"
                        />
                    </div>

                    @if ($mode === 'studio' && $selectedOptions->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-1.5 px-6 pb-6 lg:pb-0">
                            @foreach ($selectedOptions as $option)
                                <span wire:key="chip-{{ $option->id }}" class="inline-flex items-center gap-1.5 rounded-full bg-secondary px-2.5 py-1 text-[11px] font-semibold text-secondary-foreground">
                                    <span class="size-2 rounded-full" style="background: {{ $option->color_hex ?? '#f4a6bf' }}"></span>
                                    {{ $option->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <div class="hidden px-6 pb-6 pt-5 lg:block">
                        <x-designer.generate-button
                            :generating="$generating"
                            :method="$mode === 'describe' ? 'generateFromPrompt' : 'generate'"
                            :label="$mode === 'describe' ? 'Generate from description' : 'Generate cake'"
                        />
                        @error('generate') <p class="mt-3 text-sm text-destructive">{{ $message }}</p> @enderror
                        @if ($design && filled($design->preview_path))
                            <button type="button" wire:click="addDesignToCart" wire:loading.attr="disabled" wire:target="addDesignToCart" class="mt-3 hidden w-full items-center justify-center gap-2 rounded-full bg-secondary py-3.5 text-sm font-bold text-secondary-foreground disabled:opacity-70 lg:inline-flex">
                                <span wire:loading.remove wire:target="addDesignToCart" class="inline-flex items-center gap-2">
                                    <x-icon name="shopping-bag" class="size-4" /> Add design to cart
                                </span>
                                <span wire:loading.flex wire:target="addDesignToCart" class="items-center gap-2">
                                    <x-spinner /> Adding…
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-border/70 bg-background/95 p-3 backdrop-blur lg:hidden">
        <div class="flex items-center gap-3">
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs text-muted-foreground">
                    @if ($mode === 'describe')
                        Describe it · from {{ $settings->formattedBasePrice() }}
                    @else
                        {{ $selectedOptions->count() }} selected · {{ $tiers }} {{ $tiers === 1 ? 'tier' : 'tiers' }}
                    @endif
                </p>
                <p class="font-display text-lg font-bold leading-tight text-primary">{{ $estimatedPrice }}</p>
            </div>
            @if ($design && filled($design->preview_path))
                <button type="button" wire:click="addDesignToCart" wire:loading.attr="disabled" wire:target="addDesignToCart" class="inline-flex items-center justify-center rounded-full bg-secondary px-4 py-3 text-xs font-bold text-secondary-foreground disabled:opacity-70">
                    <span wire:loading.remove wire:target="addDesignToCart">Add to cart</span>
                    <span wire:loading.flex wire:target="addDesignToCart"><x-spinner class="size-3.5" /></span>
                </button>
            @endif
            <x-designer.generate-button
                compact
                :generating="$generating"
                :method="$mode === 'describe' ? 'generateFromPrompt' : 'generate'"
                :label="$mode === 'describe' ? 'Generate' : 'Generate cake'"
                class="shrink-0"
            />
        </div>
    </div>
</div>
