// Fade-in on scroll. The `js` class on <html> gates the CSS so the page is fully
// visible when JavaScript is unavailable.
document.documentElement.classList.add('js');

const observer = new IntersectionObserver(
    (entries, obs) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                obs.unobserve(entry.target);
            }
        });
    },
    { threshold: 0.05, rootMargin: '0px 0px -40px 0px' }
);

export const observeReveals = (root = document) => {
    root.querySelectorAll('.fade-in:not(.is-visible)').forEach((el) => {
        // Re-registering forces a fresh check for cards the masonry just re-parented.
        observer.unobserve(el);
        observer.observe(el);
    });
};

const boot = () => observeReveals();

document.addEventListener('DOMContentLoaded', boot);
// Masonry re-parents its cards, which detaches them from the observer.
document.addEventListener('masonry:layout', (event) => observeReveals(event.target));
boot();

window.endlessObserveReveals = observeReveals;
