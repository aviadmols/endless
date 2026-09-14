// Full-screen preview: the book as it is actually bound.
//
// A printed sheet carries two pages, one on each side, so the pages are grouped
// into leaves — leaf n is page 2n-1 on the front and page 2n on the back. Every
// leaf sits on the left half and pivots on the spine at the centre; turning it
// swings it onto the right half and shows you its back. Which is why the spread
// reads right page first, then left, the way a Hebrew book does.

const SPINE = 'book-spread-spine';

const build = (pages, vars) => {
    const overlay = document.createElement('div');
    overlay.className = 'spread';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'תצוגה מלאה של הספר');
    Object.entries(vars).forEach(([name, value]) => overlay.style.setProperty(name, value));

    const leaves = [];
    for (let i = 0; i < pages.length; i += 2) {
        leaves.push([pages[i], pages[i + 1] ?? null]);
    }

    overlay.innerHTML = `
        <button type="button" class="spread__close" aria-label="סגירה">✕</button>
        <div class="spread__frame">
            <div class="spread__stage" id="${SPINE}"></div>
        </div>
        <div class="spread__controls">
            <button type="button" class="btn btn--ghost btn--sm" data-spread-prev aria-label="הדף הקודם">→</button>
            <p class="spread__counter" aria-live="polite"></p>
            <button type="button" class="btn btn--ghost btn--sm" data-spread-next aria-label="הדף הבא">←</button>
        </div>
    `;

    const stage = overlay.querySelector(`#${SPINE}`);

    leaves.forEach(([front, back]) => {
        const leaf = document.createElement('div');
        leaf.className = 'spread__leaf';

        const face = (page, side) => {
            const el = document.createElement('div');
            el.className = `spread__face spread__face--${side}`;
            if (page) {
                el.classList.add(...Array.from(page.classList).filter((c) => c.startsWith('book__page--')));
                const source = page.querySelector('.book__face');
                if (source) el.append(...Array.from(source.cloneNode(true).childNodes));
            } else {
                el.classList.add('spread__face--empty');
            }
            return el;
        };

        leaf.append(face(front, 'front'), face(back, 'back'));
        stage.append(leaf);
    });

    return { overlay, stage, leaves: Array.from(stage.children), total: pages.length };
};

const open = (book) => {
    const pages = Array.from(book.querySelectorAll('[data-book-page]'));
    if (pages.length === 0) return;

    const styles = getComputedStyle(book);
    const inherit = (name, fallback) => styles.getPropertyValue(name).trim() || fallback;
    const { overlay, leaves, total } = build(pages, {
        '--book-ratio': inherit('--book-ratio', '0.75'),
        '--type-scale': inherit('--type-scale', '1'),
        '--cover-bg': inherit('--cover-bg', '#ffffff'),
        '--cover-ink': inherit('--cover-ink', '#1D1D20'),
    });

    document.body.append(overlay);
    document.body.classList.add('spread-open');

    const counter = overlay.querySelector('.spread__counter');
    const prev = overlay.querySelector('[data-spread-prev]');
    const next = overlay.querySelector('[data-spread-next]');
    let turned = 0;

    const render = ({ animate = true } = {}) => {
        overlay.classList.toggle('is-seeking', !animate);
        overlay.classList.toggle('is-closed', turned === 0);
        leaves.forEach((leaf, i) => {
            const isTurned = i < turned;
            leaf.classList.toggle('is-turned', isTurned);
            // Turned leaves stack upward on the right; unturned downward on the left.
            leaf.style.zIndex = String(isTurned ? i : leaves.length - i);
        });

        // Right page is the back of the last turned leaf, left is the next front.
        const right = turned > 0 ? turned * 2 : 0;
        const left = turned * 2 + 1;
        counter.textContent = right
            ? `עמודים ${right}–${Math.min(left, total)} מתוך ${total}`
            : `כריכה · ${total} עמודים`;

        prev.disabled = turned === 0;
        next.disabled = turned >= leaves.length;
        if (!animate) requestAnimationFrame(() => requestAnimationFrame(() => overlay.classList.remove('is-seeking')));
    };

    const go = (delta) => {
        turned = Math.min(leaves.length, Math.max(0, turned + delta));
        render();
    };

    const close = () => {
        overlay.remove();
        document.body.classList.remove('spread-open');
        document.removeEventListener('keydown', onKey);
    };

    function onKey(event) {
        if (event.key === 'Escape') close();
        // RTL: left arrow advances, right arrow goes back.
        if (event.key === 'ArrowLeft') go(1);
        if (event.key === 'ArrowRight') go(-1);
    }

    prev.addEventListener('click', () => go(-1));
    next.addEventListener('click', () => go(1));
    overlay.querySelector('.spread__close').addEventListener('click', close);
    overlay.addEventListener('click', (event) => { if (event.target === overlay) close(); });
    document.addEventListener('keydown', onKey);

    render({ animate: false });
    overlay.querySelector('.spread__close').focus();
};

export const bindFullscreen = (root) => {
    const button = root.querySelector('[data-book-fullscreen]');
    const book = root.querySelector('[data-book]');
    if (!button || !book) return;

    button.addEventListener('click', () => open(book));
};
