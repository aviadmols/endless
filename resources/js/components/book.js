// The book builder in the personal area: a flip-through preview that re-renders
// when the owner changes what goes in the book or what size it is printed at.

const bindFlipThrough = (root) => {
    const pages = Array.from(root.querySelectorAll('[data-book-page]'));
    if (pages.length === 0) return;

    const counter = root.querySelector('[data-book-current]');
    const scrub = root.querySelector('[data-book-scrub]');
    const prev = root.querySelector('[data-book-prev]');
    const next = root.querySelector('[data-book-next]');
    let index = 0;

    const show = (to) => {
        index = Math.min(pages.length - 1, Math.max(0, to));
        pages.forEach((page, i) => {
            page.classList.toggle('is-current', i === index);
            page.setAttribute('aria-hidden', i === index ? 'false' : 'true');
        });
        if (counter) counter.textContent = String(index + 1);
        if (scrub) scrub.value = String(index + 1);
        if (prev) prev.disabled = index === 0;
        if (next) next.disabled = index === pages.length - 1;
    };

    // RTL: the "next" arrow points left, but it still means forward.
    prev?.addEventListener('click', () => show(index - 1));
    next?.addEventListener('click', () => show(index + 1));
    // The range input carries the keyboard story — arrows, Home/End, Page up/down.
    scrub?.addEventListener('input', () => show(Number(scrub.value) - 1));

    show(0);
};

const bindCopies = (form) => {
    const box = form.querySelector('[data-copies]');
    if (!box) return;

    const input = box.querySelector('[data-copies-input]');
    const step = (delta) => {
        const min = Number(input.min || 1);
        const max = Number(input.max || 999);
        input.value = String(Math.min(max, Math.max(min, (Number(input.value) || min) + delta)));
    };

    box.querySelector('[data-copies-up]')?.addEventListener('click', () => step(1));
    box.querySelector('[data-copies-down]')?.addEventListener('click', () => step(-1));
};

const boot = () => {
    const form = document.querySelector('[data-book-form]');
    if (!form) return;

    const target = form.querySelector('[data-book-preview]');
    bindFlipThrough(target);
    bindCopies(form);

    let pending = null;

    const refresh = async () => {
        const params = new URLSearchParams({
            content: form.querySelector('input[name="content"]:checked')?.value ?? '',
            size: form.querySelector('input[name="size"]:checked')?.value ?? '',
        });

        // Only the newest request may paint.
        pending?.abort();
        const controller = new AbortController();
        pending = controller;

        target.classList.add('is-loading');

        try {
            const response = await fetch(`${form.dataset.previewUrl}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
            });
            if (!response.ok) throw new Error(response.statusText);

            target.innerHTML = await response.text();
            bindFlipThrough(target);
        } catch (error) {
            if (error.name !== 'AbortError') {
                // Leave the last good preview on screen rather than blanking it.
                console.error('book preview failed', error);
            }
        } finally {
            if (pending === controller) {
                pending = null;
                target.classList.remove('is-loading');
            }
        }
    };

    form.querySelectorAll('[data-book-option]').forEach((input) => {
        input.addEventListener('change', refresh);
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
