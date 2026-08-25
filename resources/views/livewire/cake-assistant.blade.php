<div class="mx-auto max-w-4xl px-5 py-12">
    <div class="grid gap-8 lg:grid-cols-[1.1fr_0.9fr] lg:items-start">
        <div>
            <p class="font-script text-3xl text-primary">Assistant</p>
            <h1 class="mt-1 text-4xl sm:text-5xl">Cake help, fast</h1>
            <p class="mt-3 max-w-xl text-sm text-muted-foreground">
                Look up an order, get FAQ answers, ask for recommendations by occasion or budget, or hand off to WhatsApp for a human.
            </p>

            <div class="mt-6 flex flex-wrap gap-2">
                @foreach ($suggestions as $suggestion)
                    <button
                        type="button"
                        wire:key="suggestion-{{ $loop->index }}"
                        wire:click="askSuggestion({{ \Illuminate\Support\Js::from($suggestion) }})"
                        wire:loading.attr="disabled"
                        wire:target="ask,askSuggestion"
                        class="rounded-full bg-secondary px-4 py-2.5 text-left text-xs font-semibold text-secondary-foreground transition hover:bg-primary hover:text-primary-foreground disabled:opacity-70"
                    >
                        {{ $suggestion }}
                    </button>
                @endforeach
            </div>

            <form wire:submit="ask" class="mt-6">
                <label for="cake-question" class="mb-2 block text-sm font-semibold">Your question</label>
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
                    <input
                        id="cake-question"
                        type="text"
                        wire:model="message"
                        placeholder="e.g. Status of order #42 · birthday cake under 8000"
                        class="min-w-0 flex-1 rounded-full border border-input bg-card px-5 py-3 text-sm outline-none ring-ring focus:ring-2"
                    >
                    <button type="submit" wire:loading.attr="disabled" wire:target="ask,askSuggestion" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground disabled:opacity-70 sm:min-w-36">
                        <span wire:loading.remove wire:target="ask,askSuggestion" class="inline-flex items-center gap-2">
                            <x-icon name="sparkle" class="size-4" /> Ask
                        </span>
                        <span wire:loading.flex wire:target="ask,askSuggestion" class="items-center gap-2">
                            <x-spinner /> Thinking…
                        </span>
                    </button>
                </div>
                @error('message') <p class="mt-2 text-sm text-destructive">{{ $message }}</p> @enderror
            </form>

            <section class="relative mt-8 min-h-56 overflow-hidden rounded-4xl bg-card p-6 shadow-soft sm:p-8" aria-live="polite">
                <div wire:loading.class="opacity-30" wire:target="ask,askSuggestion">
                    @if ($question !== '' && $answer !== '')
                        <p class="text-xs font-bold uppercase tracking-wider text-primary">Your question</p>
                        <h2 class="mt-1 text-2xl">{{ $question }}</h2>
                        <x-markdown :content="$answer" class="mt-5" />
                    @else
                        <p class="text-sm text-muted-foreground">
                            Try an order number, a storage question, or “recommend a wedding cake under 15000”.
                        </p>
                    @endif
                </div>
                <x-assistant.generating />
            </section>
        </div>

        <aside class="space-y-4">
            <div class="rounded-4xl bg-gradient-hero p-6 text-[color:var(--hero-deep)] shadow-soft">
                <p class="font-script text-2xl">Need a human?</p>
                <h2 class="mt-1 text-2xl font-bold">WhatsApp handoff</h2>
                <p class="mt-2 text-sm opacity-80">Chat with the bakery team for custom quotes, last-minute changes, or allergy checks.</p>
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="mt-5 inline-flex items-center gap-2 rounded-full bg-[color:var(--hero-deep)] px-5 py-3 text-sm font-bold text-primary-foreground">
                    <x-icon name="whatsapp" class="size-4" /> Message on WhatsApp
                </a>
            </div>
            <div class="rounded-4xl bg-card p-6 shadow-soft">
                <h2 class="text-lg font-bold">What I can do</h2>
                <ul class="mt-3 space-y-2 text-sm text-muted-foreground">
                    <li>Order status by ID (e.g. order #12)</li>
                    <li>FAQ on storage, lead time, delivery, designer</li>
                    <li>Cake recommendations by occasion or budget</li>
                    <li>General cake questions via AI when nothing matches</li>
                </ul>
            </div>
        </aside>
    </div>
</div>
