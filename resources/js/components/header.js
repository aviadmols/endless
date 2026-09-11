// Sticky header background + mobile menu toggle.
const header = document.querySelector('.site-header');

if (header) {
    const onScroll = () => header.classList.toggle('is-scrolled', window.scrollY > 12);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
}

const burger = document.querySelector('[data-burger]');
const menu = document.querySelector('[data-mobile-menu]');

if (burger && menu) {
    const setOpen = (open) => {
        burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        menu.hidden = !open;
        document.body.classList.toggle('menu-open', open);
    };

    setOpen(false);
    burger.addEventListener('click', () => setOpen(burger.getAttribute('aria-expanded') !== 'true'));
    menu.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setOpen(false)));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') setOpen(false);
    });
}
