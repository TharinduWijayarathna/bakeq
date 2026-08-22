<div class="mx-auto max-w-3xl px-5 py-12">
    <p class="font-script text-3xl text-primary">Cake questions</p>
    <h1 class="mt-1 text-4xl">Ask anything about cakes</h1>
    <p class="mt-3 max-w-xl text-sm text-muted-foreground">
        Get a short, plain-language answer about flavours, serving sizes, storage, delivery, or the designer.
    </p>

    <div class="mt-8 grid grid-cols-1 gap-2 sm:grid-cols-2">
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
                placeholder="e.g. How many people does a 1kg cake serve?"
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
                    Pick a suggested question, or type your own. Answers stay short so anyone can follow them.
                </p>
            @endif
        </div>
        <x-assistant.generating />
    </section>
</div>
