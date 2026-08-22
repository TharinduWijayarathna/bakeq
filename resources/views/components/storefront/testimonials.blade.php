@props([
    'testimonials',
])

@if ($testimonials->isNotEmpty())
    <section
        id="reviews"
        class="mx-auto max-w-5xl px-5 pb-20"
        x-reveal
        x-data="{
            active: 0,
            count: {{ $testimonials->count() }},
            timer: null,
            next() {
                if (this.count < 2) {
                    return;
                }
                this.active = (this.active + 1) % this.count;
            },
            previous() {
                if (this.count < 2) {
                    return;
                }
                this.active = (this.active - 1 + this.count) % this.count;
            },
            go(index) {
                this.active = index;
            },
            start() {
                this.stop();
                if (this.count < 2) {
                    return;
                }
                this.timer = setInterval(() => this.next(), 6000);
            },
            stop() {
                if (this.timer === null) {
                    return;
                }
                clearInterval(this.timer);
                this.timer = null;
            },
            init() {
                this.start();
            },
        }"
        x-on:mouseenter="stop()"
        x-on:mouseleave="start()"
    >
        <div class="text-center">
            <p class="font-script text-3xl text-primary">What guests say</p>
            <h2 class="mt-1 text-4xl sm:text-5xl">Loved at every celebration</h2>
        </div>

        <div class="relative mt-10">
            <div class="overflow-hidden">
                <div
                    class="flex transition-transform duration-500 ease-out"
                    :style="{ transform: 'translateX(-' + (active * 100) + '%)' }"
                >
                    @foreach ($testimonials as $index => $testimonial)
                        <article
                            wire:key="home-testimonial-{{ $testimonial->id }}"
                            class="w-full shrink-0 px-2 text-center"
                            :aria-hidden="active === {{ $index }} ? 'false' : 'true'"
                        >
                            <div class="rounded-4xl bg-card px-6 py-10 shadow-soft sm:px-12">
                                <div class="flex justify-center gap-1 text-primary">
                                    @foreach (range(1, 5) as $star)
                                        <x-icon
                                            name="star"
                                            @class([
                                                'size-5',
                                                'fill-current' => $star <= $testimonial->rating,
                                                'opacity-25' => $star > $testimonial->rating,
                                            ])
                                        />
                                    @endforeach
                                </div>
                                <blockquote class="mt-6 text-2xl leading-snug sm:text-3xl">
                                    <span class="text-gradient-sweet font-display">“{{ $testimonial->quote }}”</span>
                                </blockquote>
                                <p class="mt-4 text-sm font-semibold text-muted-foreground">
                                    {{ $testimonial->author }}
                                    @if ($testimonial->occasion)
                                        <span> • {{ $testimonial->occasion }}</span>
                                    @endif
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

            @if ($testimonials->count() > 1)
                <div class="mt-6 flex items-center justify-center gap-4">
                    <button
                        type="button"
                        x-on:click="previous()"
                        class="grid size-11 place-items-center rounded-full bg-card text-foreground shadow-soft transition hover:-translate-y-0.5"
                        aria-label="Previous testimonial"
                    >
                        <x-icon name="arrow-left" class="size-4" />
                    </button>
                    <div class="flex items-center gap-2">
                        @foreach ($testimonials as $index => $testimonial)
                            <button
                                type="button"
                                wire:key="testimonial-dot-{{ $testimonial->id }}"
                                x-on:click="go({{ $index }})"
                                class="size-2.5 rounded-full transition"
                                :class="active === {{ $index }} ? 'bg-primary scale-125' : 'bg-primary/30'"
                                aria-label="Show testimonial {{ $index + 1 }}"
                            ></button>
                        @endforeach
                    </div>
                    <button
                        type="button"
                        x-on:click="next()"
                        class="grid size-11 place-items-center rounded-full bg-card text-foreground shadow-soft transition hover:-translate-y-0.5"
                        aria-label="Next testimonial"
                    >
                        <x-icon name="arrow-right" class="size-4" />
                    </button>
                </div>
            @endif
        </div>
    </section>
@endif
