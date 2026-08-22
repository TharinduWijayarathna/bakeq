document.addEventListener('alpine:init', () => {
    Alpine.data('lazyImage', () => ({
        loaded: false,
        init() {
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

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (! entry.isIntersecting) {
                        return;
                    }

                    el.classList.add('is-visible');
                    observer.unobserve(el);
                });
            },
            {
                threshold: 0.14,
                rootMargin: '0px 0px -6% 0px',
            },
        );

        observer.observe(el);

        cleanup(() => observer.disconnect());
    });
});
