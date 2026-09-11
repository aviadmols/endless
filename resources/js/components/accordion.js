// Accordion sections in the memorial editor. Opens the section named in location.hash.
const setOpen = (acc, open) => {
    const head = acc.querySelector('.accordion__head');
    const body = acc.querySelector('.accordion__body');
    acc.classList.toggle('is-open', open);
    body.hidden = !open;
    head.setAttribute('aria-expanded', open ? 'true' : 'false');
};

const init = () => {
    const accordions = Array.from(document.querySelectorAll('[data-accordion]'));
    if (!accordions.length) return;

    const hash = window.location.hash.replace('#', '');

    accordions.forEach((acc) => {
        const head = acc.querySelector('.accordion__head');
        const body = acc.querySelector('.accordion__body');
        if (!head || !body) return;

        setOpen(acc, acc.dataset.open !== undefined || (hash !== '' && acc.id === hash));
        head.addEventListener('click', (event) => {
            event.preventDefault();
            setOpen(acc, !acc.classList.contains('is-open'));
        });
    });

    if (hash) {
        const target = document.getElementById(hash);
        if (target?.hasAttribute('data-accordion')) {
            setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'start' }), 80);
        }
    }
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
