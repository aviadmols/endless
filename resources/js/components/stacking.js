// Stacking cards on the home page.
//
// The cards are `position: sticky`, so they park on top of each other as you scroll.
// This adds the second half of the effect: every card that is already covered shrinks
// and desaturates, so the stack reads as depth rather than as four flat sheets.
//
// The numbers mirror the reference page exactly — each card below the top one loses
// 6% of its size and gains 30% grayscale, interpolated from the moment its own quarter
// of the wrapper reaches the top of the viewport until the wrapper's bottom reaches
// the middle of it.

const SCALE_STEP = 0.06;
const GRAYSCALE_STEP = 30;

const clamp = (value) => Math.min(1, Math.max(0, value));

const setup = (wrapper) => {
    const cards = Array.from(wrapper.querySelectorAll('.stack-card'));
    if (cards.length === 0) return;

    const count = cards.length;
    let bounds = null;

    const measure = () => {
        const rect = wrapper.getBoundingClientRect();
        const top = rect.top + window.scrollY;
        bounds = { top, height: rect.height };
    };

    const render = () => {
        if (!bounds) return;

        const end = bounds.top + bounds.height - window.innerHeight / 2;

        cards.forEach((card, index) => {
            const depth = count - 1 - index; // how many cards will land on top of this one
            if (depth === 0) {
                card.style.transform = '';
                card.style.filter = '';
                return;
            }

            const start = bounds.top + (index / count) * bounds.height;
            const span = end - start;
            const progress = span > 0 ? clamp((window.scrollY - start) / span) : 0;

            card.style.transform = `translate3d(0, 0, 0) scale(${1 - SCALE_STEP * depth * progress})`;
            card.style.filter = `grayscale(${GRAYSCALE_STEP * depth * progress}%)`;
        });
    };

    let queued = false;
    const onScroll = () => {
        if (queued) return;
        queued = true;
        requestAnimationFrame(() => {
            queued = false;
            render();
        });
    };

    const onResize = () => {
        measure();
        render();
    };

    measure();
    render();

    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', onResize);
    // The card images change the wrapper's height as they load.
    window.addEventListener('load', onResize);
};

const boot = () => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    document.querySelectorAll('[data-stack-cards]').forEach(setup);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
