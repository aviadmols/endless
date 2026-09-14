// Turns a native <select> into a listbox whose options carry an icon.
//
// The <select> stays in the DOM and remains the value the form submits, so
// without JavaScript the field is simply the browser's own select.

const enhance = (field) => {
    const native = field.querySelector('.icon-select__native');
    const button = field.querySelector('.icon-select__button');
    const list = field.querySelector('.icon-select__list');
    const label = field.querySelector('label');
    const slot = field.querySelector('[data-icon-slot]');
    const value = field.querySelector('.icon-select__value');
    const options = Array.from(list.querySelectorAll('.icon-select__option'));

    if (!native || !button || !list || options.length === 0) return;

    field.classList.add('is-enhanced');
    button.hidden = false;
    native.tabIndex = -1;
    native.setAttribute('aria-hidden', 'true');
    if (label) label.htmlFor = button.id;

    let activeIndex = Math.max(0, options.findIndex((o) => o.dataset.value === native.value));

    const setActive = (index) => {
        activeIndex = (index + options.length) % options.length;
        options.forEach((o, i) => o.classList.toggle('is-active', i === activeIndex));
        const active = options[activeIndex];
        button.setAttribute('aria-activedescendant', active.id);
        active.scrollIntoView({ block: 'nearest' });
    };

    const choose = (index) => {
        const option = options[index];
        native.value = option.dataset.value;
        native.dispatchEvent(new Event('change', { bubbles: true }));

        options.forEach((o, i) => o.setAttribute('aria-selected', i === index ? 'true' : 'false'));
        value.textContent = option.lastElementChild.textContent;
        // Clone rather than copy markup, so nothing is re-parsed as HTML.
        slot.replaceChildren(...Array.from(option.firstElementChild.cloneNode(true).childNodes));
    };

    const isOpen = () => button.getAttribute('aria-expanded') === 'true';

    const setOpen = (open) => {
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        list.hidden = !open;
        if (open) setActive(options.findIndex((o) => o.dataset.value === native.value));
        else button.removeAttribute('aria-activedescendant');
    };

    setOpen(false);

    button.addEventListener('click', () => setOpen(!isOpen()));

    button.addEventListener('keydown', (event) => {
        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                if (!isOpen()) setOpen(true);
                else setActive(activeIndex + 1);
                break;
            case 'ArrowUp':
                event.preventDefault();
                if (!isOpen()) setOpen(true);
                else setActive(activeIndex - 1);
                break;
            case 'Home':
                if (isOpen()) { event.preventDefault(); setActive(0); }
                break;
            case 'End':
                if (isOpen()) { event.preventDefault(); setActive(options.length - 1); }
                break;
            case 'Enter':
            case ' ':
                event.preventDefault();
                if (isOpen()) { choose(activeIndex); setOpen(false); } else setOpen(true);
                break;
            case 'Escape':
                if (isOpen()) { event.preventDefault(); setOpen(false); }
                break;
            case 'Tab':
                setOpen(false);
                break;
        }
    });

    options.forEach((option, index) => {
        option.addEventListener('click', () => {
            choose(index);
            setOpen(false);
            button.focus();
        });
        option.addEventListener('mousemove', () => setActive(index));
    });

    document.addEventListener('click', (event) => {
        if (isOpen() && !field.contains(event.target)) setOpen(false);
    });
};

const boot = () => document.querySelectorAll('[data-icon-select]').forEach(enhance);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
