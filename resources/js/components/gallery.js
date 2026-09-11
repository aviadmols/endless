import Alpine from 'alpinejs';

/** Lightbox for the "moments" gallery. */
Alpine.data('lightbox', (images = []) => ({
    images,
    open: false,
    index: 0,

    show(i) {
        this.index = i;
        this.open = true;
        document.body.style.overflow = 'hidden';
    },
    close() {
        this.open = false;
        document.body.style.overflow = '';
    },
    next() {
        this.index = (this.index + 1) % this.images.length;
    },
    prev() {
        this.index = (this.index - 1 + this.images.length) % this.images.length;
    },
    onKey(event) {
        if (!this.open) return;
        if (event.key === 'Escape') this.close();
        if (event.key === 'ArrowRight') this.prev();
        if (event.key === 'ArrowLeft') this.next();
    },
}));
