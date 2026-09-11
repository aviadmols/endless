/**
 * Masonry that fills row-first (each item goes to the currently shortest column),
 * matching the reference layout. Plain CSS `columns` fills column-first, which
 * reorders the cards, so the placement is done here instead.
 *
 * Markup: <div class="masonry" data-masonry data-cols-sm="1" data-cols-md="2" data-cols-lg="3"> …items… </div>
 */
const layouts = new WeakMap();

const columnCount = (el) => {
    const w = window.innerWidth;
    const sm = parseInt(el.dataset.colsSm || '1', 10);
    const md = parseInt(el.dataset.colsMd || '2', 10);
    const lg = parseInt(el.dataset.colsLg || '3', 10);
    if (w < 600) return sm;
    if (w < 900) return md;
    return lg;
};

const layout = (el) => {
    const items = layouts.get(el) || [];
    if (!items.length) return;

    const cols = columnCount(el);
    el.innerHTML = '';

    const columns = Array.from({ length: cols }, () => {
        const div = document.createElement('div');
        div.className = 'masonry__col';
        el.appendChild(div);
        return div;
    });

    // Track an estimated height per column so cards spread evenly before images load.
    const heights = new Array(cols).fill(0);
    items.forEach((item) => {
        const shortest = heights.indexOf(Math.min(...heights));
        columns[shortest].appendChild(item);
        heights[shortest] += estimate(item, columns[shortest].clientWidth || 1);
    });

    // Moving nodes detaches them, so anything watching them has to look again.
    el.dispatchEvent(new CustomEvent('masonry:layout', { bubbles: true }));
};

const estimate = (item, width) => {
    const img = item.querySelector('img');
    const w = Number(img?.getAttribute('width'));
    const h = Number(img?.getAttribute('height'));
    if (w && h) return (width * h) / w + 20;
    return item.getBoundingClientRect().height || 260;
};

export const registerMasonry = (el) => {
    if (!el) return;
    const existing = layouts.get(el) || [];
    const items = existing.length ? existing : Array.from(el.children);
    layouts.set(el, items);
    layout(el);
};

export const appendToMasonry = (el, nodes) => {
    const items = layouts.get(el) || [];
    layouts.set(el, items.concat(nodes));
    layout(el);
};

const boot = () => document.querySelectorAll('[data-masonry]').forEach(registerMasonry);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}

let resizeTimer;
let lastWidth = window.innerWidth;
window.addEventListener('resize', () => {
    if (window.innerWidth === lastWidth) return;
    lastWidth = window.innerWidth;
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => document.querySelectorAll('[data-masonry]').forEach(layout), 150);
});

// Re-balance once images have their real size.
window.addEventListener('load', () => document.querySelectorAll('[data-masonry]').forEach(layout));
