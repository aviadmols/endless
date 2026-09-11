// Marks empty date/time inputs so their "dd/mm/yyyy" hint can be dimmed in CSS.
const mark = (input) => input.classList.toggle('is-empty', input.value === '');

const boot = () => {
    document.querySelectorAll('input[type="date"], input[type="time"], input[type="datetime-local"]').forEach((input) => {
        mark(input);
        input.addEventListener('change', () => mark(input));
        input.addEventListener('input', () => mark(input));
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
