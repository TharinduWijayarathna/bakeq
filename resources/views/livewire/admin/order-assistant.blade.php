<div>
    <p class="font-script text-3xl text-primary">Inbox</p>
    <h1 class="mt-1 text-4xl">Order Assistant</h1>
    <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
        Paste a WhatsApp, SMS, or email message. Gemini extracts the cake details into an editable form, then you send them to POS.
    </p>
    <x-flash />

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="rounded-4xl bg-card p-6 shadow-soft">
            <h2 class="text-lg font-bold">Customer message</h2>
            <textarea
                wire:model="message"
                rows="12"
                placeholder="Hi, need a chocolate birthday cake for 15 people this Saturday afternoon, budget around 8000, with pink flowers please…"
                class="mt-4 w-full rounded-3xl border border-input bg-background px-4 py-3 text-sm leading-relaxed"
            ></textarea>
            @error('message') <p class="mt-2 text-sm text-destructive">{{ $message }}</p> @enderror

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
            @if (! $extracted)
                <p class="mt-4 text-sm text-muted-foreground">Results appear here after extraction. You can always edit before sending to POS.</p>
            @else
                @if ($failed)
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
                        <select wire:model="user_id" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
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
                        <input type="text" wire:model="date" class="w-full rounded-2xl border border-input px-3 py-2.5 text-sm">
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
</div>
