document.addEventListener('alpine:init', () => {
    Alpine.data('lazyImage', () => ({
        loaded: false,
        init() {
            this.syncLoaded();

            this.$el.addEventListener('load', () => {
                this.loaded = true;
            });

            // Livewire morph can remount Alpine on cached images that never fire "load" again.
            queueMicrotask(() => this.syncLoaded());
        },
        syncLoaded() {
            if (this.$el.complete && this.$el.naturalWidth > 0) {
                this.loaded = true;
            }
        },
    }));

    Alpine.directive('reveal', (el, { modifiers }, { cleanup }) => {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            el.classList.add('is-visible');

            return;
        }

        el.classList.add('reveal');

        if (modifiers.includes('stagger')) {
            el.classList.add('reveal-stagger');
        }

        if (modifiers.includes('left')) {
            el.classList.add('reveal-left');
        } else if (modifiers.includes('right')) {
            el.classList.add('reveal-right');
        } else if (modifiers.includes('scale')) {
            el.classList.add('reveal-scale');
        }

        let revealed = false;

        const show = () => {
            revealed = true;
            el.classList.add('is-visible');
        };

        const restore = () => {
            if (revealed) {
                el.classList.add('is-visible');

                return;
            }

            const rect = el.getBoundingClientRect();
            const inView = rect.bottom > 0 && rect.top < (window.innerHeight || document.documentElement.clientHeight);

            if (inView) {
                show();
            }
        };

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (! entry.isIntersecting) {
                        return;
                    }

                    show();
                    observer.unobserve(el);
                });
            },
            {
                threshold: 0.14,
                rootMargin: '0px 0px -6% 0px',
            },
        );

        observer.observe(el);

        // Livewire morph resets class attributes from server HTML, which removes
        // Alpine's is-visible and leaves content stuck at opacity: 0.
        const onMorphed = () => queueMicrotask(restore);

        document.addEventListener('livewire:navigated', onMorphed);

        let removeMorphHook = () => {};

        const registerMorphHook = () => {
            if (! window.Livewire?.hook) {
                return;
            }

            removeMorphHook = Livewire.hook('morphed', ({ el: root }) => {
                if (root === el || root.contains(el)) {
                    onMorphed();
                }
            });
        };

        if (window.Livewire) {
            registerMorphHook();
        } else {
            document.addEventListener('livewire:init', registerMorphHook, { once: true });
        }

        cleanup(() => {
            observer.disconnect();
            document.removeEventListener('livewire:navigated', onMorphed);
            removeMorphHook();
        });
    });
});

document.addEventListener('livewire:init', () => {
    Livewire.hook('morphed', () => {
        queueMicrotask(() => {
            document.querySelectorAll('.reveal').forEach((el) => {
                if (el.classList.contains('is-visible')) {
                    return;
                }

                const rect = el.getBoundingClientRect();
                const inView = rect.bottom > 0 && rect.top < (window.innerHeight || document.documentElement.clientHeight);

                if (inView) {
                    el.classList.add('is-visible');
                }
            });

            document.querySelectorAll('[x-data="lazyImage"]').forEach((el) => {
                if (el.complete && el.naturalWidth > 0) {
                    el.classList.remove('opacity-0');
                    el.classList.add('opacity-100');
                }
            });
        });
    });
});
