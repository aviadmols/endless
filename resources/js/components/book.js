// The book builder in the personal area.
//
// The preview is a stack of leaves: every page before the current one is turned
// over onto the spine, so advancing and going back are the same operation and the
// CSS transition does the animating. Changing an option re-renders the stack.

const bindFlipThrough = (root, onPageChange) => {
    const book = root.querySelector('[data-book]');
    const pages = Array.from(root.querySelectorAll('[data-book-page]'));
    if (!book || pages.length === 0) return null;

    const counter = root.querySelector('[data-book-current]');
    const scrub = root.querySelector('[data-book-scrub]');
    const prev = root.querySelector('[data-book-prev]');
    const next = root.querySelector('[data-book-next]');
    let index = 0;

    const show = (to, { animate = true } = {}) => {
        const target = Math.min(pages.length - 1, Math.max(0, to));
        // Turning twenty leaves at once looks like a flutter, not a page turn.
        const seeking = !animate || Math.abs(target - index) > 1;
        book.classList.toggle('is-seeking', seeking);

        index = target;
        pages.forEach((page, i) => {
            const turned = i < index;
            page.classList.toggle('is-turned', turned);
            // Unturned leaves stack with the top page first; turned ones invert.
            page.style.zIndex = String(turned ? i : pages.length - i);
            page.setAttribute('aria-hidden', i === index ? 'false' : 'true');
        });

        if (seeking) {
            // Let the jump paint before re-arming the transition.
            requestAnimationFrame(() => requestAnimationFrame(() => book.classList.remove('is-seeking')));
        }

        if (counter) counter.textContent = String(index + 1);
        if (scrub) scrub.value = String(index + 1);
        if (prev) prev.disabled = index === 0;
        if (next) next.disabled = index === pages.length - 1;
        onPageChange?.(pages[index], index);
    };

    // RTL: the "next" arrow points left, but it still means forward.
    prev?.addEventListener('click', () => show(index - 1));
    next?.addEventListener('click', () => show(index + 1));
    // The range input carries the keyboard story — arrows, Home/End, Page up/down.
    scrub?.addEventListener('input', () => show(Number(scrub.value) - 1));

    const openAt = Number(book.dataset.openAt || 1) - 1;
    show(Math.min(openAt, pages.length - 1), { animate: false });

    return { show, count: pages.length };
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

/** Points the edit form at whichever page is showing. */
const bindEditor = (editor) => {
    if (!editor) return () => {};

    const rows = Array.from(editor.querySelectorAll('[data-field-row]'));
    const empty = editor.querySelector('[data-editor-empty]');
    const body = editor.querySelector('[data-editor-body]');
    const keyInput = editor.querySelector('input[name="key"]');
    const pageInput = editor.querySelector('input[name="page"]');

    return (page, index) => {
        const fields = (page?.dataset.fields || '').split(',').filter(Boolean);
        const editable = fields.length > 0;

        if (empty) empty.hidden = editable;
        if (body) body.hidden = !editable;
        if (keyInput) keyInput.value = page?.dataset.key ?? '';
        if (pageInput) pageInput.value = String(index + 1);

        rows.forEach((row) => {
            const name = row.dataset.fieldRow;
            const input = row.querySelector('input, textarea');
            const applies = fields.includes(name);
            row.hidden = !applies;
            // A hidden field must not submit, or it would blank the page's text.
            if (input) {
                input.disabled = !applies;
                if (applies) input.value = page?.dataset[name] ?? '';
            }
        });
    };
};

const boot = () => {
    const form = document.querySelector('[data-book-form]');
    if (!form) return;

    const target = form.querySelector('[data-book-preview]');
    const syncEditor = bindEditor(document.querySelector('[data-book-editor]'));
    bindFlipThrough(target, syncEditor);
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
            bindFlipThrough(target, syncEditor);
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
        input.addEventListener('change', () => {
            // The editor form has to save against the options being previewed.
            document.querySelectorAll('[data-mirror]').forEach((mirror) => {
                mirror.value = form.querySelector(`input[name="${mirror.dataset.mirror}"]:checked`)?.value ?? '';
            });
            refresh();
        });
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
