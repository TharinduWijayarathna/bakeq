<div class="mx-auto flex h-[calc(100vh-8rem)] max-w-5xl flex-col">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="font-script text-3xl text-primary">Agent</p>
            <h1 class="mt-1 text-4xl">Admin Agent</h1>
            <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                Chat to run bakery operations (orders, production, cakes, customers, inventory, waste, POS, and dashboard insights) through secure tools.
            </p>
        </div>
        @if ($messages !== [])
            <button
                type="button"
                wire:click="clearChat"
                class="rounded-md border border-border px-3 py-1.5 text-[11px] font-bold uppercase tracking-wider text-muted-foreground hover:bg-muted"
            >
                Clear chat
            </button>
        @endif
    </div>

    <div class="mt-3 flex flex-wrap gap-1.5">
        @foreach ($suggestions as $suggestion)
            <button
                type="button"
                wire:key="agent-suggestion-{{ $loop->index }}"
                wire:click="askSuggestion({{ \Illuminate\Support\Js::from($suggestion) }})"
                wire:loading.attr="disabled"
                wire:target="send,askSuggestion"
                class="rounded-md bg-secondary px-2.5 py-1 text-[11px] font-semibold leading-tight text-secondary-foreground transition hover:bg-primary hover:text-primary-foreground disabled:opacity-70"
            >
                {{ $suggestion }}
            </button>
        @endforeach
    </div>

    <section
        class="relative mt-4 flex min-h-0 flex-1 flex-col overflow-hidden rounded-4xl bg-card shadow-soft"
        aria-live="polite"
    >
        <div
            class="flex-1 space-y-4 overflow-y-auto p-5 sm:p-6"
            wire:loading.class="opacity-60"
            wire:target="send,askSuggestion"
        >
            @forelse ($messages as $index => $entry)
                <div wire:key="agent-msg-{{ $index }}" @class([
                    'flex',
                    'justify-end' => $entry['role'] === 'user',
                    'justify-start' => $entry['role'] !== 'user',
                ])>
                    <div @class([
                        'max-w-[95%] rounded-3xl px-4 py-3 text-sm sm:max-w-[88%]',
                        'bg-primary text-primary-foreground' => $entry['role'] === 'user',
                        'bg-background text-foreground ring-1 ring-border' => $entry['role'] !== 'user',
                    ])>
                        @if ($entry['role'] === 'user')
                            <p class="whitespace-pre-wrap">{{ $entry['body'] }}</p>
                        @else
                            <x-markdown :content="$entry['body']" class="markdown-answer--agent" />
                        @endif
                    </div>
                </div>
            @empty
                <div class="flex h-full min-h-64 flex-col items-center justify-center px-6 text-center">
                    <x-icon name="sparkle" class="size-8 text-primary" />
                    <h2 class="mt-3 text-xl font-bold">Ask the bakery agent</h2>
                    <p class="mt-2 max-w-md text-sm text-muted-foreground">
                        Examples: “Mark order #42 as baking”, “Create category Cupcakes”, “Add 2kg flour to stock”, “POS sale for customer 5, chocolate cake cash”.
                    </p>
                </div>
            @endforelse
        </div>

        <x-assistant.generating target="send,askSuggestion" title="Working on it" />

        <form wire:submit="send" class="border-t border-border p-4 sm:p-5">
            <label for="admin-agent-message" class="sr-only">Message</label>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <textarea
                    id="admin-agent-message"
                    wire:model="message"
                    rows="2"
                    placeholder="Tell the agent what to do…"
                    class="min-w-0 flex-1 rounded-3xl border border-input bg-background px-4 py-3 text-sm outline-none ring-ring focus:ring-2"
                ></textarea>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="send,askSuggestion"
                    class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-bold text-primary-foreground disabled:opacity-70"
                >
                    <span wire:loading.remove wire:target="send,askSuggestion" class="inline-flex items-center gap-2">
                        <x-icon name="sparkle" class="size-4" /> Send
                    </span>
                    <span wire:loading.flex wire:target="send,askSuggestion" class="items-center gap-2">
                        <x-spinner /> Working…
                    </span>
                </button>
            </div>
            @error('message') <p class="mt-2 text-sm text-destructive">{{ $message }}</p> @enderror
        </form>
    </section>
</div>
